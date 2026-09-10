<?php

declare(strict_types=1);

namespace Yume\Shared\Clock;

use Yume\Contracts\Clock\ClockInterface;

/** Test double: lets expiry and rollout logic be asserted without sleeping. */
final class FrozenClock implements ClockInterface
{
    private \DateTimeImmutable $now;

    public function __construct(?\DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function timestamp(): int
    {
        return $this->now->getTimestamp();
    }

    public function advance(int $seconds): void
    {
        $this->now = $this->now->modify(sprintf('%+d seconds', $seconds));
    }

    public function set(\DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
