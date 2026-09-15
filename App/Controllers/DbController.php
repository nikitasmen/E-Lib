<?php

namespace App\Controllers;

use App\Database\DatabaseInterface;
use App\Factory\DatabaseFactory;
use Exception;

/**
 * @deprecated Use App\Factory\DatabaseFactory instead
 * This class is maintained for backward compatibility and delegates all calls
 * to the DatabaseInterface obtained from DatabaseFactory.
 */
class DbController
{
    private static ?self $instance = null;
    private DatabaseInterface $repository;

    private function __construct()
    {
        $this->repository = DatabaseFactory::getDatabase();

        // Display a deprecation warning in development environments
        if (getenv('APP_ENV') !== 'production') {
            trigger_error(
                'DbController is deprecated. Use App\Repository\DatabaseRepository instead.',
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * Get singleton instance
     *
     * @param string|null $dbName Optional database name (currently unused: DatabaseFactory
     *   connects to a hardcoded database name; kept for API compatibility with existing callers).
     * @return DbController
     */
    public static function getInstance($dbName = null)
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @deprecated Use DatabaseRepository::insert instead
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function insert(string $collection, array $data): array
    {
        return $this->repository->insert($collection, $data);
    }

    /**
     * @deprecated Use DatabaseRepository::find instead
     * @param array<string, mixed> $filter
     * @return list<array<string, mixed>>
     */
    public function find(string $collection, array $filter = []): array
    {
        return $this->repository->find($collection, $filter);
    }

    /**
     * @deprecated Use DatabaseRepository::findOne instead
     * @param array<string, mixed> $filter
     * @return array<string, mixed>|null
     */
    public function findOne(string $collection, array $filter = []): ?array
    {
        return $this->repository->findOne($collection, $filter);
    }

    /**
     * @deprecated Use DatabaseRepository::update instead
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $update
     * @return array<string, mixed>
     */
    public function update(string $collection, array $filter, array $update): array
    {
        return $this->repository->update($collection, $filter, $update);
    }

    /**
     * @deprecated Use DatabaseRepository::delete instead
     * @param array<string, mixed> $filter
     * @return array<string, mixed>
     */
    public function delete(string $collection, array $filter): array
    {
        return $this->repository->delete($collection, $filter);
    }

    /**
     * @deprecated Use DatabaseRepository::aggregate instead
     * @param array<int, array<string, mixed>> $pipeline
     * @return array<int, mixed>
     */
    public function getFeatured(string $collection, array $pipeline): array
    {
        return $this->repository->aggregate($collection, $pipeline);
    }
}
