<?php

declare(strict_types=1);

namespace Yume\Contracts\Clock;

/**
 * Time as an injected dependency, never as a global side effect.
 *
 * Domain code MUST depend on this instead of calling time() or new DateTimeImmutable(),
 * so that expiry, rate-limit and rollout logic stays deterministically testable.
 */
interface ClockInterface
{
    public function now(): \DateTimeImmutable;

    public function timestamp(): int;
}
