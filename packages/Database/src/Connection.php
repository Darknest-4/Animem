<?php

declare(strict_types=1);

namespace Yume\Database;

use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Database\Exception\QueryException;

/**
 * PDO connection with prepared statements as the only query path.
 *
 * Notable differences from the legacy `Connect::getconn()`:
 *  - the PDO handle is created once and reused (the old singleton reconnected on
 *    every call, opening dozens of MySQL connections per request);
 *  - emulated prepares are off, so parameters are bound server-side;
 *  - errors throw and are reported without the SQL text ever reaching the client.
 */
final class Connection implements ConnectionInterface
{
    private ?\PDO $pdo = null;

    private int $transactionDepth = 0;

    public function __construct(
        private readonly string $dsn,
        private readonly string $username,
        private readonly string $password,
        private readonly int $connectTimeoutSeconds = 5,
    ) {
    }

    public function pdo(): \PDO
    {
        if ($this->pdo instanceof \PDO) {
            return $this->pdo;
        }

        try {
            $this->pdo = new \PDO($this->dsn, $this->username, $this->password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_TIMEOUT => $this->connectTimeoutSeconds,
            ]);
        } catch (\PDOException $e) {
            // The DSN carries the host and database name but never the password;
            // even so the message is not propagated to the HTTP layer.
            throw new QueryException('Database connection failed.', previous: $e);
        }

        return $this->pdo;
    }

    public function select(string $sql, array $params = []): array
    {
        return $this->statement($sql, $params)->fetchAll();
    }

    public function selectOne(string $sql, array $params = []): ?array
    {
        $row = $this->statement($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public function scalar(string $sql, array $params = []): mixed
    {
        $value = $this->statement($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->statement($sql, $params)->rowCount();
    }

    public function executeScript(string $sql): void
    {
        try {
            // PDO::exec() uses the simple query protocol, which accepts several
            // commands at once; prepare() cannot. Never reachable from request input.
            $this->pdo()->exec($sql);
        } catch (\PDOException $e) {
            throw QueryException::fromPdo($e, $sql);
        }
    }

    public function transaction(callable $callback): mixed
    {
        $this->begin();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();

            throw $e;
        }
    }

    public function inTransaction(): bool
    {
        return $this->transactionDepth > 0;
    }

    /** Nested transactions map onto savepoints so handlers can compose safely. */
    private function begin(): void
    {
        if ($this->transactionDepth === 0) {
            $this->pdo()->beginTransaction();
        } else {
            $this->pdo()->exec('SAVEPOINT yume_sp_' . $this->transactionDepth);
        }

        ++$this->transactionDepth;
    }

    private function commit(): void
    {
        --$this->transactionDepth;

        if ($this->transactionDepth === 0) {
            $this->pdo()->commit();
        } else {
            $this->pdo()->exec('RELEASE SAVEPOINT yume_sp_' . $this->transactionDepth);
        }
    }

    private function rollBack(): void
    {
        --$this->transactionDepth;

        if ($this->transactionDepth === 0) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
        } else {
            $this->pdo()->exec('ROLLBACK TO SAVEPOINT yume_sp_' . $this->transactionDepth);
        }
    }

    /** @param array<string, mixed> $params */
    private function statement(string $sql, array $params): \PDOStatement
    {
        try {
            $statement = $this->pdo()->prepare($sql);

            foreach ($params as $name => $value) {
                $statement->bindValue(
                    is_int($name) ? $name + 1 : ':' . ltrim((string) $name, ':'),
                    $value,
                    self::pdoType($value),
                );
            }

            $statement->execute();

            return $statement;
        } catch (\PDOException $e) {
            throw QueryException::fromPdo($e, $sql);
        }
    }

    private static function pdoType(mixed $value): int
    {
        return match (true) {
            $value === null => \PDO::PARAM_NULL,
            is_bool($value) => \PDO::PARAM_BOOL,
            is_int($value) => \PDO::PARAM_INT,
            default => \PDO::PARAM_STR,
        };
    }
}
