<?php

declare(strict_types=1);

namespace Yume\Contracts\Cache;

interface CacheInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttlSeconds = 300): void;

    public function delete(string $key): void;

    /** @param callable(): mixed $producer */
    public function remember(string $key, int $ttlSeconds, callable $producer): mixed;

    public function clear(): void;
}
