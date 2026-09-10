<?php

declare(strict_types=1);

namespace Yume\Database\Migration;

use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Persistence\ConnectionInterface;

/**
 * Forward-only SQL migrations, applied inside a transaction and recorded in
 * schema_migrations. The schema is therefore reproducible from the repository
 * alone — the single biggest gap in the legacy project, whose ~37 tables existed
 * only inside the production database.
 */
final class Migrator
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly ClockInterface $clock,
        private readonly string $migrationsPath,
    ) {
    }

    /** @return list<string> the versions applied by this run */
    public function migrate(): array
    {
        $this->ensureRegistry();

        $applied = $this->appliedVersions();
        $ran = [];

        foreach ($this->pendingFiles($applied) as $version => $file) {
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException(sprintf('Unable to read migration "%s".', $file));
            }

            $startedAt = microtime(true);

            $this->connection->transaction(function (ConnectionInterface $connection) use ($sql, $version, $startedAt): void {
                // Migration bodies are repository-controlled DDL, not user input,
                // and contain many statements — hence executeScript() rather than
                // the parameterised path.
                $connection->executeScript($sql);
                $connection->execute(
                    'INSERT INTO schema_migrations (version, applied_at, duration_ms) VALUES (:version, :applied_at, :duration_ms)',
                    [
                        'version' => $version,
                        'applied_at' => $this->clock->now()->format('Y-m-d H:i:sP'),
                        'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    ],
                );
            });

            $ran[] = $version;
        }

        return $ran;
    }

    /** @return list<string> */
    public function pending(): array
    {
        $this->ensureRegistry();

        return array_keys($this->pendingFiles($this->appliedVersions()));
    }

    /** @return list<string> */
    public function applied(): array
    {
        $this->ensureRegistry();

        return $this->appliedVersions();
    }

    private function ensureRegistry(): void
    {
        $this->connection->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS schema_migrations (
                version     TEXT PRIMARY KEY,
                applied_at  TIMESTAMPTZ NOT NULL,
                duration_ms INTEGER NOT NULL DEFAULT 0
            )
            SQL);
    }

    /** @return list<string> */
    private function appliedVersions(): array
    {
        $rows = $this->connection->select('SELECT version FROM schema_migrations ORDER BY version');

        return array_map(static fn (array $row): string => (string) $row['version'], $rows);
    }

    /**
     * @param list<string> $applied
     * @return array<string, string> version => absolute file path, ordered by version
     */
    private function pendingFiles(array $applied): array
    {
        $files = glob(rtrim($this->migrationsPath, '/') . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $pending = [];
        foreach ($files as $file) {
            $version = basename($file, '.sql');
            if (!in_array($version, $applied, true)) {
                $pending[$version] = $file;
            }
        }

        return $pending;
    }
}
