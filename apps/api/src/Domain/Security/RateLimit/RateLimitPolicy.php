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
     * Defaults for the endpoints that actually get attacked.
     *
     * Note that *asking* for a link and *redeeming* one are separate budgets on
     * purpose. Sharing them means a user who fumbles their new password twice
     * can no longer complete the reset they legitimately started — the endpoints
     * look similar but the abuse they invite is different:
     *
     *   email_dispatch  costs us outbound mail and lets an attacker spam a
     *                   third party's inbox, so it is strict and subnet-keyed.
     *   token_redeem    costs nothing and already requires a secret the caller
     *                   was mailed, so it only needs to stop brute force.
     *
     * @return array<string, self>
     */
    public static function defaults(): array
    {
        return [
            // Credential stuffing is the realistic threat here.
            'auth.login' => new self('auth.login', 5, 300, 900),
            'auth.register' => new self('auth.register', 3, 3600, 3600),
            // Consumed inside the handlers that actually send mail.
            'auth.email_dispatch' => new self('auth.email_dispatch', 3, 3600, 1800),
            // Applied at the route: redeeming a mailed token.
            'auth.token_redeem' => new self('auth.token_redeem', 10, 900),
            'auth.password_change' => new self('auth.password_change', 5, 900),
            'api.read' => new self('api.read', 300, 60),
            'api.write' => new self('api.write', 60, 60),
        ];
    }
}
