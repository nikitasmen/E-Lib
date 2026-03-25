<?php

namespace App\Models;

use App\Factory\DatabaseFactory;
use App\Helpers\Database\MongoHelper;
use InvalidArgumentException;

/**
 * BaseModel provides common functionality for all models
 * and ensures consistent data access patterns
 */
abstract class BaseModel
{
    protected $db;
    protected $collection;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = DatabaseFactory::getDatabase();
    }

    /**
     * Validate data according to model rules
     *
     * @param array $data The data to validate
     * @return array Any validation errors
     */
    abstract public function validate(array $data): array;

    /**
     * Ensure the ID is a valid MongoDB ObjectId
     *
     * @param string $id
     * @return \MongoDB\BSON\ObjectId
     * @throws InvalidArgumentException
     */
    protected function ensureId(string $id)
    {
        if (!preg_match('/^[0-9a-f]{24}$/', $id)) {
            throw new InvalidArgumentException('Invalid ID format');
        }
        return MongoHelper::createObjectId($id);
    }

    /**
     * Find all documents matching filter
     *
     * @param array $filter
     * @param array $options
     * @return array
     */
    public function findAll(array $filter = [], array $options = []): array
    {
        return $this->db->find($this->collection, $filter, $options);
    }

    /**
     * Find document by ID
     *
     * @param string $id
     * @return array|null
     * @throws InvalidArgumentException
     */
    public function findById(string $id)
    {
        return $this->db->findOne($this->collection, ['_id' => $this->ensureId($id)]);
    }

    /**
     * Create a new document
     *
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }
        return $this->db->insert($this->collection, $data);
    }

    /**
     * Update document by ID
     *
     * @param string $id
     * @param array $data
     * @return array|false
     * @throws InvalidArgumentException
     */
    public function updateById(string $id, array $data)
    {
        return $this->db->update(
            $this->collection,
            ['_id' => $this->ensureId($id)],
            ['$set' => $data]
        );
    }

    /**
     * Delete document by ID
     *
     * @param string $id
     * @return array
     * @throws InvalidArgumentException
     */
    public function deleteById(string $id): array
    {
        return $this->db->delete($this->collection, ['_id' => $this->ensureId($id)]);
    }
}
