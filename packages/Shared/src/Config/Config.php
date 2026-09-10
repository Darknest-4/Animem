<?php

declare(strict_types=1);

namespace Yume\Shared\Config;

/**
 * Immutable, dot-addressable configuration loaded from apps/api/config/*.php.
 *
 * Config files read the environment; nothing else in the codebase calls getenv()
 * at runtime, so the full configuration surface is greppable in one directory.
 */
final class Config
{
    /** @param array<string, mixed> $items */
    private function __construct(private readonly array $items)
    {
    }

    public static function fromDirectory(string $directory): self
    {
        $items = [];

        foreach (glob(rtrim($directory, '/') . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $loaded = require $file;

            if (!is_array($loaded)) {
                throw new \RuntimeException(sprintf('Config file "%s" must return an array.', $file));
            }

            $items[$key] = $loaded;
        }

        return new self($items);
    }

    /** @param array<string, mixed> $items */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return is_bool($value) ? $value : $default;
    }

    /** @return array<mixed> */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }
}
