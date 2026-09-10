<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\RateLimit;

interface RateLimiterInterface
{
    /** Consumes one unit of the budget and reports whether the request may proceed. */
    public function consume(RateLimitPolicy $policy, string $key, \DateTimeImmutable $now): RateLimitResult;

    /** Reads the budget without consuming it. */
    public function peek(RateLimitPolicy $policy, string $key, \DateTimeImmutable $now): RateLimitResult;

    /** Called after a successful login so one typo does not cost the whole budget. */
    public function reset(RateLimitPolicy $policy, string $key): void;
}
