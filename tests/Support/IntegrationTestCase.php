<?php

namespace Tests\Support;

use App\Database\MongoDatabase;
use MongoDB\Client;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that run models against a real MongoDB, for what mocks can't check: the queries
 * themselves (filters, upserts, unique _ids, aggregations on CI's MongoDB 4.4).
 *
 * Opt-in via MONGO_TEST_URI (never MONGO_URI, so a local .env pointing at Atlas is never touched).
 * Each test gets its own throwaway database, dropped afterwards. Skipped when the variable is unset.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Database $mongo;
    protected MongoDatabase $db;

    protected function setUp(): void
    {
        $uri = getenv('MONGO_TEST_URI');
        if ($uri === false || $uri === '') {
            $this->markTestSkipped('MONGO_TEST_URI is not set');
        }
        $this->mongo = (new Client($uri))->selectDatabase('elib_test_' . bin2hex(random_bytes(6)));
        $this->db = new MongoDatabase($this->mongo);
    }

    protected function tearDown(): void
    {
        if (isset($this->mongo)) {
            $this->mongo->drop();
        }
    }
}
