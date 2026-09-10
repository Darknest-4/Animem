<?php

declare(strict_types=1);

namespace Yume\Shared\Clock;

use Yume\Contracts\Clock\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function timestamp(): int
    {
        return $this->now()->getTimestamp();
    }
}
