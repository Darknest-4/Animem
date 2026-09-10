<?php

declare(strict_types=1);

namespace Yume\Shared\Support;

/**
 * Reads configuration from the process environment only.
 *
 * Secrets never live in a tracked file: the legacy site shipped its production
 * database password in four committed JSON files, which is precisely what this
 * class exists to prevent.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $overrides = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (isset(self::$overrides[$key])) {
            return self::$overrides[$key];
        }

        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            throw new \RuntimeException(sprintf(
                'Required environment variable "%s" is not set. Copy .env.example to .env and fill it in.',
                $key,
            ));
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return $value === null ? $default : (int) $value;
    }

    /** @return list<string> */
    public static function list(string $key, array $default = []): array
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $v): bool => $v !== ''));
    }

    /** Test seam only. */
    public static function override(string $key, string $value): void
    {
        self::$overrides[$key] = $value;
    }

    public static function reset(): void
    {
        self::$overrides = [];
    }

    /**
     * Loads a dotenv file into the process environment without overwriting
     * variables the container already provides (env always wins over file).
     */
    public static function loadFile(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (strlen($value) >= 2
                && ($value[0] === '"' || $value[0] === "'")
                && $value[strlen($value) - 1] === $value[0]
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}
