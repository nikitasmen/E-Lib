<?php

namespace App\Models;

use App\Repository\DatabaseRepository;
use App\Helpers\Database\MongoHelper;
use InvalidArgumentException;

/**
 * Users model handles all user-related data operations
 */
class Users extends BaseModel
{
    protected $collection = 'Users';

    /**
     * Required fields for user registration
     */
    private const REQUIRED_FIELDS = ['email', 'password', 'username'];

    /**
     * Get a user by their email address
     *
     * @param string $email User's email
     * @return array|null User data or null if not found
     * @throws InvalidArgumentException If email is invalid
     */
    public function getUserByEmail(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        return $this->db->findOne($this->collection, ['email' => $email]);
    }

    /**
     * Register a new user
     *
     * @param array $user User data
     * @return array Insert operation result
     * @throws InvalidArgumentException If validation fails
     */
    public function registerUser(array $user): array
    {
        return $this->create($user);
    }

    /**
     * Authenticate a user
     *
     * @param string $email User's email
     * @param string $password User's password
     * @return array|false User data if authentication succeeds, false otherwise
     */
    public function login(string $email, string $password)
    {
        $user = $this->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Get a user by their ID
     *
     * @param string $id User ID
     * @return array|null User data or null if not found
     */
    public function getUserById(string $id)
    {
        try {
            return $this->findById($id);
        } catch (\Exception $e) {
            // Handle case where ID isn't a valid ObjectId
            error_log("Invalid user ID format: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate user data
     *
     * @param array $data User data to validate
     * @return array Validation errors
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Check required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Check if email already exists when registering
        if (!empty($data['email']) && empty($errors['email'])) {
            $existingUser = $this->getUserByEmail($data['email']);
            if ($existingUser && (!isset($data['_id']) || $existingUser['_id'] != $data['_id'])) {
                $errors['email'] = 'Email already in use';
            }
        }

        // Password strength validation
        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        return $errors;
    }

    /**
     * Save a book to user's saved books
     *
     * @param string $userId User ID
     * @param string $bookId Book ID
     * @return array|bool Update result or false if user not found or book already saved
     */
    public function saveBook(string $userId, string $bookId)
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }

        // Convert MongoDB BSONArray to PHP array if needed
        $savedBooksOriginal = $user['savedBooks'] ?? [];

        // Convert to PHP array if it's a MongoDB\Model\BSONArray
        $savedBooks = is_object($savedBooksOriginal) && method_exists($savedBooksOriginal, 'getArrayCopy')
            ? $savedBooksOriginal->getArrayCopy()
            : (array)$savedBooksOriginal;

        if (!in_array($bookId, $savedBooks)) {
            $savedBooks[] = $bookId;
            try {
                return $this->updateById($userId, ['savedBooks' => $savedBooks]);
            } catch (\Exception $e) {
                error_log("Error saving book: " . $e->getMessage());
                return false;
            }
        }

        return true; // Book already saved
    }

    /**
     * Remove a book from user's saved books
     *
     * @param string $userId User ID
     * @param string $bookId Book ID
     * @return array|bool Update result or false if user not found
     */
    public function removeBook(string $userId, string $bookId)
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }

        // Convert MongoDB BSONArray to PHP array if needed
        $savedBooksOriginal = $user['savedBooks'] ?? [];

        // Convert to PHP array if it's a MongoDB\Model\BSONArray
        $savedBooks = is_object($savedBooksOriginal) && method_exists($savedBooksOriginal, 'getArrayCopy')
            ? $savedBooksOriginal->getArrayCopy()
            : (array)$savedBooksOriginal;

        if (in_array($bookId, $savedBooks)) {
            $savedBooks = array_diff($savedBooks, [$bookId]);
            try {
                return $this->updateById($userId, ['savedBooks' => $savedBooks]);
            } catch (\Exception $e) {
                error_log("Error removing book: " . $e->getMessage());
                return false;
            }
        }

        return true; // Book wasn't saved
    }

    /**
     * Update user profile
     *
     * @param string $userId User ID
     * @param array $userData Updated user data
     * @return array|false Update result or false if validation fails
     * @throws InvalidArgumentException If validation fails
     */
    public function updateProfile(string $userId, array $userData)
    {
        // Filter out sensitive fields that shouldn't be updated directly
        $protectedFields = ['password', 'role', '_id', 'email'];
        $filteredData = array_diff_key($userData, array_flip($protectedFields));

        // Validate the filtered data
        $errors = $this->validateProfileUpdate($filteredData);
        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        return $this->updateById($userId, $filteredData);
    }

    /**
     * Validate profile update data
     *
     * @param array $data Profile data to validate
     * @return array Validation errors
     */
    private function validateProfileUpdate(array $data): array
    {
        $errors = [];

        // Name validation
        if (isset($data['name']) && strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }

        // Add other profile field validations as needed

        return $errors;
    }
    /**
     * Update user profile information
     *
     * @param string $userId The ID of the user to update
     * @param array $updates Associative array of fields to update
     * @return bool True on success, false on failure
     */
    public function updateUser($userId, array $updates)
    {
        try {
            // Make sure the user exists
            $user = $this->getUserById($userId);
            if (!$user) {
                return false;
            }

            // Update the user document with the provided fields
            $result = $this->updateById($userId, $updates);

            return $result !== false;
        } catch (\Exception $e) {
            error_log('Error updating user: ' . $e->getMessage());
            return false;
        }
    }
}
