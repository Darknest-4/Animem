<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Cache;

use Yume\Contracts\Cache\CacheInterface;

/**
 * Per-container in-memory cache.
 *
 * Falls back to a request-scoped array when APCu is unavailable (CLI, tests), so
 * calling code never has to branch on availability.
 */
final class ApcuCache implements CacheInterface
{
    private readonly bool $apcuAvailable;

    /** @var array<string, array{value: mixed, expires: int}> */
    private array $fallback = [];

    public function __construct(private readonly string $prefix = 'yume:')
    {
        $this->apcuAvailable = function_exists('apcu_fetch') && filter_var(
            ini_get('apc.enabled'),
            FILTER_VALIDATE_BOOL,
        );
    }

    public function get(string $key): mixed
    {
        $key = $this->prefix . $key;

        if ($this->apcuAvailable) {
            $success = false;
            $value = apcu_fetch($key, $success);

            return $success ? $value : null;
        }

        $entry = $this->fallback[$key] ?? null;

        if ($entry === null || $entry['expires'] < time()) {
            unset($this->fallback[$key]);

            return null;
        }

        return $entry['value'];
    }

    public function set(string $key, mixed $value, int $ttlSeconds = 300): void
    {
        $key = $this->prefix . $key;

        if ($this->apcuAvailable) {
            apcu_store($key, $value, $ttlSeconds);

            return;
        }

        $this->fallback[$key] = ['value' => $value, 'expires' => time() + $ttlSeconds];
    }

    public function delete(string $key): void
    {
        $key = $this->prefix . $key;

        if ($this->apcuAvailable) {
            apcu_delete($key);

            return;
        }

        unset($this->fallback[$key]);
    }

    public function remember(string $key, int $ttlSeconds, callable $producer): mixed
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $producer();
        $this->set($key, $value, $ttlSeconds);

        return $value;
    }

    public function clear(): void
    {
        if ($this->apcuAvailable) {
            apcu_clear_cache();
        }

        $this->fallback = [];
    }
}
