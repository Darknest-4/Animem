<?php

declare(strict_types=1);

namespace Yume\Tests\Support;

use PHPUnit\Framework\TestCase;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Database\Connection;
use Yume\Database\Migration\Migrator;
use Yume\Shared\Clock\SystemClock;

/**
 * Base class for tests that need a real PostgreSQL database.
 *
 * The schema is created by running the actual migrations, not by a fixture
 * dump: that way a migration that works in the test suite is a migration that
 * works in production, and a broken one fails here first.
 *
 * When no database is configured the tests skip with an explanatory message
 * rather than failing, so `composer test:unit` stays useful on a laptop.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected ConnectionInterface $connection;

    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        $dsn = getenv('TEST_DB_DSN');

        if ($dsn === false || $dsn === '') {
            self::markTestSkipped(
                'Set TEST_DB_DSN (and TEST_DB_USERNAME / TEST_DB_PASSWORD) to run database tests. '
                . 'Inside the stack: make test.',
            );
        }

        $this->connection = new Connection(
            $dsn,
            (string) (getenv('TEST_DB_USERNAME') ?: 'yume'),
            (string) (getenv('TEST_DB_PASSWORD') ?: ''),
        );

        if (!self::$migrated) {
            (new Migrator(
                $this->connection,
                new SystemClock(),
                dirname(__DIR__, 2) . '/src/Infrastructure/Persistence/Migration',
            ))->migrate();

            self::$migrated = true;
        }

        $this->truncateMutableTables();
    }

    /**
     * Resets rows between tests while leaving the seeded roles, permissions and
     * feature flags intact — those are schema, not fixtures.
     */
    protected function truncateMutableTables(): void
    {
        $this->connection->executeScript(
            'TRUNCATE users, user_credentials, sessions, user_roles, security_events, '
            . 'bans, rate_limit_hits, jobs, network_reputation, '
            . 'email_verification_tokens, password_reset_tokens RESTART IDENTITY CASCADE',
        );
    }
}
