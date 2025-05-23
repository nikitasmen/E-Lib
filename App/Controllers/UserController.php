<?php
namespace App\Controllers;

use App\Includes\Environment;
use App\Services\UserService; 
use App\Services\BookService;
use App\Services\EmailService;
use App\Includes\ResponseHandler;
use App\Includes\JwtHelper;

class UserController {
    private $userService;
    private $bookService;
    private $emailService;
    
    public function __construct() {
        $this->userService = new UserService();
        $this->bookService = new BookService();
        $this->emailService = new EmailService();
    }

    public function handleLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_POST)) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, true);
            $email = $input['email'] ?? null;
            $password = $input['password'] ?? null;
        } else {
            $email = $_POST['email'] ?? null;
            $password = $_POST['password'] ?? null;
        }

        $user = $this->userService->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            $payload = [
                'user_id' => $user['_id'],
                'email' => $user['email']
            ];
            $token = JwtHelper::generateToken($payload);

            $_SESSION['user_id'] = $user['_id'];
            $_SESSION['token'] = $token;
            $_SESSION['username'] = $user['username'];
            $_SESSION['isAdmin'] = $user['isAdmin'] ?? false;

            ResponseHandler::respond(true, [
                'token' => $token, 
                'user' => [
                    'id' => $user['_id'],
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'isAdmin' => $user['isAdmin'] ?? false
                ]
            ], 200);
        } else {
            ResponseHandler::respond(false, 'Invalid credentials', 401);
        }
    }

    public function handleLogout() {
        // Logout logic here...
        if (!isset($_SESSION['user_id'])) {
            ResponseHandler::respond(false, 'No user logged in', 401);
            return;
        }
        $_SESSION = [];
        session_destroy();
        ResponseHandler::respond(true, 'Logout successful');
       
    }

    public function handleSignup() {
        
        if (empty($_POST)) {
            // Try to read from input stream (for JSON requests)
            $inputJSON = file_get_contents('php://input');
            error_log('Raw input: ' . $inputJSON);
            $input = json_decode($inputJSON, true);
            
            if ($input) {
                $userName = $input['username'] ?? null;
                $email = $input['email'] ?? null; 
                $password = $input['password'] ?? null;
            } else {
                ResponseHandler::respond(false, 'No data received', 400);
                return;
            }
        } else {
            // Get from POST (for form submissions)
            $userName = $_POST['username'] ?? null;
            $email = $_POST['email'] ?? null;
            $password = $_POST['password'] ?? null;
        }
        
        // Continue with your validation
        if (empty($userName) || empty($email) || empty($password)) {
            ResponseHandler::respond(false, 'All fields are required', 400);
            return;
        }
        
        $existingUser = $this->userService->getUserByEmail($email);
        if ($existingUser) {
            ResponseHandler::respond(false, 'Email already exists', 400);
            return;
        }
        // Validate the input data
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseHandler::respond(false, 'Invalid email format', 400);
            return;
        }
        if ($this->userService->registerUser($userName, $email, $password)) {
            ResponseHandler::respond(true, 'User created successfully', 200);
        } else {
            ResponseHandler::respond(false, 'User creation failed', 400);
        }
    }

    public function getUser($id) {
        $user = $this->userService->getUserById($id);
        if ($user) {
            ResponseHandler::respond(true, $user, 200);
        } else {
            ResponseHandler::respond(false, 'User not found', 404);
        }   
    }

    public function saveBook() { 
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['user_id'])) {
            ResponseHandler::respond(false, 'User not authenticated', 401);
            return;
        }
        
        if (empty($_POST)) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, true);
            $bookId = $input['book_id'] ?? null;
        } else {
            $bookId = $_POST['book_id'] ?? null;
        }

        if (empty($bookId)) {
            ResponseHandler::respond(false, 'Book ID is required', 400);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if ($this->userService->saveBook($userId, $bookId)) {
            ResponseHandler::respond(true, 'Book saved successfully', 200);
        } else {
            ResponseHandler::respond(false, 'Failed to save book', 400);
        }
    }

    public function getSavedBooks() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['user_id'])) {
            ResponseHandler::respond(false, 'User not authenticated', 401);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $bookIds = $this->userService->getSavedBooks($userId);
        
        if (!empty($bookIds)) {
            $books = [];
            foreach ($bookIds as $bookId) {
                $book = $this->bookService->getBookDetails($bookId);
                if ($book) {
                    $books[] = $book;
                }
            }
            
            if (!empty($books)) {
                ResponseHandler::respond(true, $books, 200);
            } 
        }
        ResponseHandler::respond(true, 'No saved books found', 404);
        
    }

    public function removeBook() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['user_id'])) {
            ResponseHandler::respond(false, 'User not authenticated', 401);
            return;
        }
        
        if (empty($_POST)) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, true);
            $bookId = $input['book_id'] ?? null;
        } else {
            $bookId = $_POST['book_id'] ?? null;
        }

        if (empty($bookId)) {
            ResponseHandler::respond(false, 'Book ID is required', 400);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if ($this->userService->removeBook($userId, $bookId)) {
            ResponseHandler::respond(true, 'Book removed successfully', 200);
        } else {
            ResponseHandler::respond(false, 'Failed to remove book', 400);
        }
    }
    
    /**
     * View error logs (admin only)
     */
    public function viewLogs() {
        // Check if user is admin
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // First verify JWT token (should be handled by middleware)
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? null;
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            ResponseHandler::respond(false, 'Unauthorized access', 401);
            exit();
        }

        // Even with valid token, check if user is admin in session
        if (empty($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
            ResponseHandler::respond(false, 'Unauthorized: Admin privileges required', 403);
            return;
        }
        
        $logPath = dirname(__DIR__, 2) . '/storage/logs/php_errors.log';
        $requestLogPath = dirname(__DIR__, 2) . '/storage/logs/requests.log';
        
        $logs = [];
        
        // Check if error log exists and is readable
        if (file_exists($logPath) && is_readable($logPath)) {
            // Get the last 100 lines (adjust as needed)
            $errorLogs = $this->getTailOfFile($logPath, 100);
            $logs['errors'] = $errorLogs;
        } else {
            $logs['errors'] = 'Error log file not found or not readable';
        }
        
        // Check if request log exists and is readable
        if (file_exists($requestLogPath) && is_readable($requestLogPath)) {
            $requestLogs = $this->getTailOfFile($requestLogPath, 50);
            $logs['requests'] = $requestLogs;
        } else {
            $logs['requests'] = 'Request log file not found or not readable';
        }
        
        // Send logs as JSON response
        ResponseHandler::respond(true, 'Logs retrieved successfully', 200, $logs);
    }
    
    /**
     * Helper method to get the last N lines of a file
     */
    private function getTailOfFile($filePath, $lines = 100) {
        $handle = fopen($filePath, "r");
        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = [];
        
        while ($linecounter > 0) {
            $t = " ";
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos--;
            }
            
            if ($beginning) {
                rewind($handle);
            }
            
            $text[] = fgets($handle);
            
            if ($beginning) {
                break;
            }
            
            $linecounter--;
        }
        
        fclose($handle);
        return array_reverse($text);
    }

    /**
     * Update user profile information
     * Currently supports updating username
     */
    public function updateProfile() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['user_id'])) {
            ResponseHandler::respond(false, 'User not authenticated', 401);
            return;
        }
        
        // Get input data
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);
        
        if (!$input) {
            ResponseHandler::respond(false, 'No data received', 400);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $updates = [];
        
        // Handle username update
        if (isset($input['username'])) {
            $newUsername = trim($input['username']);
            
            // Validate username
            if (empty($newUsername)) {
                ResponseHandler::respond(false, 'Username cannot be empty', 400);
                return;
            }
            
            if (strlen($newUsername) < 3) {
                ResponseHandler::respond(false, 'Username must be at least 3 characters', 400);
                return;
            }
            
            $updates['username'] = $newUsername;
        }
        
        // No updates to process
        if (empty($updates)) {
            ResponseHandler::respond(false, 'No valid updates provided', 400);
            return;
        }
        
        // Update the user profile
        $result = $this->userService->updateUser($userId, $updates);
        
        if ($result) {
            // Update session data if username was changed
            if (isset($updates['username'])) {
                $_SESSION['username'] = $updates['username'];
            }
            
            ResponseHandler::respond(true, 'Profile updated successfully', 200);
        } else {
            ResponseHandler::respond(false, 'Failed to update profile', 500);
        }
    }

    public function support() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Process form data
        $name = $_POST['name'] ?? 'Anonymous';
        $email = $_POST['email'] ?? 'no-reply@example.com';
        $message = $_POST['message'] ?? null;
        
        // For regular JSON requests (backward compatibility)
        if (empty($_POST) && empty($_FILES)) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, true);
            $name = $input['name'] ?? $name;
            $email = $input['email'] ?? $email;
            $message = $input['message'] ?? $message;
        }

        if (empty($message)) {
            ResponseHandler::respond(false, 'Message is required', 400);
            return;
        }

        try {
            // Process uploaded embedded images (if any)
            $attachments = [];
            
            if (!empty($_FILES['embedded_images']) && is_array($_FILES['embedded_images']['name'])) {
                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/support/';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Process each uploaded file
                $fileCount = count($_FILES['embedded_images']['name']);
                
                // Limit to 5 files max
                $fileCount = min($fileCount, 5);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    // Skip files with errors
                    if ($_FILES['embedded_images']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    
                    // Validate file size (5MB max)
                    if ($_FILES['embedded_images']['size'][$i] > 5 * 1024 * 1024) {
                        continue;
                    }
                    
                    // Validate mime type
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($_FILES['embedded_images']['tmp_name'][$i]);
                    
                    if (!in_array($mimeType, $allowedTypes)) {
                        continue;
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($_FILES['embedded_images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = uniqid('support_', true) . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    // Move the uploaded file to the destination
                    if (move_uploaded_file($_FILES['embedded_images']['tmp_name'][$i], $filepath)) {
                        // Add to attachments array
                        $attachments[] = [
                            'path' => $filepath,
                            'filename' => $_FILES['embedded_images']['name'][$i],
                            'type' => $mimeType
                        ];
                    }
                }
            }
            
            // Use the EmailService with PHPMailer to send email with attachments
            $result = $this->emailService->sendSupportEmail($email, $name, $message, $attachments);
            
            if ($result) {
                ResponseHandler::respond(true, 'Support request sent successfully', 200);
            } else {
                throw new \Exception('Email sending failed');
            }
            
        } catch (\Exception $e) {
            // Log the error
            error_log('Error sending support email: ' . $e->getMessage());
            ResponseHandler::respond(false, 'Failed to send support request. Please try again later.', 500);
        }
    }
}