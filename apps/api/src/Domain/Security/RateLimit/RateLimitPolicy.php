<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\RateLimit;

/** A named budget: N attempts per window, with an optional penalty on exhaustion. */
final readonly class RateLimitPolicy
{
    public function __construct(
        public string $name,
        public int $maxAttempts,
        public int $windowSeconds,
        /** Extra lockout applied once the budget is exhausted. */
        public int $penaltySeconds = 0,
    ) {
        if ($maxAttempts < 1 || $windowSeconds < 1) {
            throw new \InvalidArgumentException('Rate limit policy needs a positive budget and window.');
        }
    }

    /**
     * Sensible defaults for the endpoints that actually get attacked.
     *
     * @return array<string, self>
     */
    public static function defaults(): array
    {
        return [
            // Deliberately strict: credential stuffing is the realistic threat here.
            'auth.login' => new self('auth.login', 5, 300, 900),
            'auth.register' => new self('auth.register', 3, 3600, 3600),
            'auth.password_reset' => new self('auth.password_reset', 3, 3600, 1800),
            'api.read' => new self('api.read', 300, 60),
            'api.write' => new self('api.write', 60, 60),
        ];
    }
}
