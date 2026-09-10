<?php

declare(strict_types=1);

namespace Yume\Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Structural guarantee that the legacy SQL-injection class of bug cannot return.
 *
 * The old codebase contained no prepared statements at all: every query was
 * built by concatenating request data into a string, including the value of a
 * client-controlled `userID` cookie. This test fails the build if a SQL string
 * anywhere in src/ or packages/ interpolates a variable.
 */
final class SqlInjectionSurfaceTest extends TestCase
{
    /** Repository code legitimately builds no SQL from variables at all. */
    public function testNoSqlStringInterpolatesAVariable(): void
    {
        $violations = [];

        foreach ($this->sourceFiles() as $file) {
            foreach (file($file) ?: [] as $number => $line) {
                if (!$this->looksLikeSql($line)) {
                    continue;
                }

                // "{$var}" or '. $var .' inside a line that also contains SQL keywords.
                if (preg_match('/\{\$[a-zA-Z_]/', $line) === 1
                    || preg_match('/[\'"]\s*\.\s*\$[a-zA-Z_]/', $line) === 1
                ) {
                    $violations[] = sprintf('%s:%d  %s', $this->relative($file), $number + 1, trim($line));
                }
            }
        }

        self::assertSame([], $violations, "SQL built by string interpolation:\n" . implode("\n", $violations));
    }

    /**
     * executeScript() bypasses parameter binding by design, for migrations only.
     * If anything else starts calling it, that is the moment to notice.
     */
    public function testExecuteScriptIsOnlyCalledByTheMigratorAndTestSupport(): void
    {
        $allowed = [
            'packages/Database/src/Migration/Migrator.php',
            'packages/Database/src/Connection.php',
            'packages/Contracts/src/Persistence/ConnectionInterface.php',
        ];

        $callers = [];

        foreach ($this->sourceFiles() as $file) {
            $contents = file_get_contents($file) ?: '';

            if (str_contains($contents, 'executeScript(') && !in_array($this->relative($file), $allowed, true)) {
                $callers[] = $this->relative($file);
            }
        }

        self::assertSame([], $callers, 'unexpected executeScript() callers: ' . implode(', ', $callers));
    }

    /** The mysqli-era helpers must not reappear anywhere. */
    public function testLegacyDatabaseHelpersAreAbsent(): void
    {
        $banned = ['mysqli_query', 'mysql_query', 'real_escape_string', 'addslashes'];
        $violations = [];

        foreach ($this->sourceFiles() as $file) {
            $contents = file_get_contents($file) ?: '';

            foreach ($banned as $needle) {
                if (str_contains($contents, $needle)) {
                    $violations[] = $this->relative($file) . ' uses ' . $needle;
                }
            }
        }

        self::assertSame([], $violations, implode("\n", $violations));
    }

    private function looksLikeSql(string $line): bool
    {
        return preg_match('/\b(SELECT|INSERT\s+INTO|UPDATE|DELETE\s+FROM|WHERE|VALUES|JOIN)\b/i', $line) === 1;
    }

    private function relative(string $file): string
    {
        return str_replace(dirname(__DIR__, 3) . '/', '', $file);
    }

    /** @return list<string> */
    private function sourceFiles(): array
    {
        $roots = [
            dirname(__DIR__, 2) . '/api/src',
            dirname(__DIR__, 3) . '/packages',
        ];

        $files = [];

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }
}
