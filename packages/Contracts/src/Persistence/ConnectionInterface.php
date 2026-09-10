<?php

declare(strict_types=1);

namespace Yume\Contracts\Persistence;

/**
 * The only way application code is allowed to reach the database.
 *
 * Every method takes bound parameters separately from the SQL string; there is no
 * entry point that accepts a pre-interpolated query. This is deliberate: it makes
 * the SQL-injection class of bug that sank the legacy codebase structurally impossible.
 */
interface ConnectionInterface
{
    /** @param array<string, mixed> $params @return list<array<string, mixed>> */
    public function select(string $sql, array $params = []): array;

    /** @param array<string, mixed> $params @return array<string, mixed>|null */
    public function selectOne(string $sql, array $params = []): ?array;

    /** @param array<string, mixed> $params */
    public function scalar(string $sql, array $params = []): mixed;

    /** @param array<string, mixed> $params @return int affected rows */
    public function execute(string $sql, array $params = []): int;

    /**
     * Runs a multi-statement SQL script with no parameter binding.
     *
     * The ONLY legitimate caller is the migrator, feeding it a .sql file from the
     * repository. It exists because a prepared statement cannot carry more than
     * one command, not as an escape hatch: passing user input here reintroduces
     * exactly the injection surface the rest of this interface removes.
     */
    public function executeScript(string $sql): void;

    /**
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;

    public function inTransaction(): bool;
}
