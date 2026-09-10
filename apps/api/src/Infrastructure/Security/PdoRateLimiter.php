<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Security;

use Yume\Api\Domain\Security\RateLimit\RateLimiterInterface;
use Yume\Api\Domain\Security\RateLimit\RateLimitPolicy;
use Yume\Api\Domain\Security\RateLimit\RateLimitResult;
use Yume\Contracts\Persistence\ConnectionInterface;

/**
 * Fixed-window rate limiter backed by PostgreSQL.
 *
 * The increment is a single atomic upsert, so concurrent requests cannot both
 * read "4 of 5 used" and both proceed. Chosen over Redis because it keeps the
 * production stack at one stateful service; the interface is the seam through
 * which a Redis implementation drops in when the write rate justifies it.
 */
final class PdoRateLimiter implements RateLimiterInterface
{
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function consume(RateLimitPolicy $policy, string $key, \DateTimeImmutable $now): RateLimitResult
    {
        $bucket = $this->bucketKey($policy, $key);
        $windowStart = $this->windowStart($policy, $now);
        $expiresAt = $windowStart->modify(sprintf('+%d seconds', $policy->windowSeconds + $policy->penaltySeconds));

        $hits = (int) $this->connection->scalar(
            <<<'SQL'
            INSERT INTO rate_limit_hits (bucket_key, window_start, hits, expires_at)
            VALUES (:bucket_key, :window_start, 1, :expires_at)
            ON CONFLICT (bucket_key, window_start)
            DO UPDATE SET hits = rate_limit_hits.hits + 1
            RETURNING hits
            SQL,
            [
                'bucket_key' => $bucket,
                'window_start' => $windowStart->format('Y-m-d H:i:sP'),
                'expires_at' => $expiresAt->format('Y-m-d H:i:sP'),
            ],
        );

        return $this->result($policy, $hits, $windowStart, $now);
    }

    public function peek(RateLimitPolicy $policy, string $key, \DateTimeImmutable $now): RateLimitResult
    {
        $windowStart = $this->windowStart($policy, $now);

        $hits = (int) ($this->connection->scalar(
            'SELECT hits FROM rate_limit_hits WHERE bucket_key = :bucket_key AND window_start = :window_start',
            [
                'bucket_key' => $this->bucketKey($policy, $key),
                'window_start' => $windowStart->format('Y-m-d H:i:sP'),
            ],
        ) ?? 0);

        return $this->result($policy, $hits, $windowStart, $now);
    }

    public function reset(RateLimitPolicy $policy, string $key): void
    {
        $this->connection->execute(
            'DELETE FROM rate_limit_hits WHERE bucket_key = :bucket_key',
            ['bucket_key' => $this->bucketKey($policy, $key)],
        );
    }

    /** Housekeeping for the worker. */
    public function purgeExpired(\DateTimeImmutable $now): int
    {
        return $this->connection->execute(
            'DELETE FROM rate_limit_hits WHERE expires_at < :now',
            ['now' => $now->format('Y-m-d H:i:sP')],
        );
    }

    private function result(
        RateLimitPolicy $policy,
        int $hits,
        \DateTimeImmutable $windowStart,
        \DateTimeImmutable $now,
    ): RateLimitResult {
        $allowed = $hits <= $policy->maxAttempts;
        $resetsAt = $windowStart->modify(sprintf('+%d seconds', $policy->windowSeconds));

        // Exhausting the budget adds the penalty on top of the remaining window,
        // so a burst of failed logins costs more than the window alone.
        if (!$allowed && $policy->penaltySeconds > 0) {
            $resetsAt = $resetsAt->modify(sprintf('+%d seconds', $policy->penaltySeconds));
        }

        return new RateLimitResult(
            $allowed,
            $policy->maxAttempts - $hits,
            $policy->maxAttempts,
            max(1, $resetsAt->getTimestamp() - $now->getTimestamp()),
            $resetsAt,
        );
    }

    private function bucketKey(RateLimitPolicy $policy, string $key): string
    {
        // Hashed so a raw identifier (which may be an email address) never lands
        // in a table that is dumped for debugging.
        return $policy->name . ':' . hash('sha256', $key);
    }

    private function windowStart(RateLimitPolicy $policy, \DateTimeImmutable $now): \DateTimeImmutable
    {
        $aligned = $now->getTimestamp() - ($now->getTimestamp() % $policy->windowSeconds);

        return (new \DateTimeImmutable('@' . $aligned))->setTimezone(new \DateTimeZone('UTC'));
    }
}
