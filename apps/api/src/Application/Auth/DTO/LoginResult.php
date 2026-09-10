<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\DTO;

final readonly class LoginResult
{
    public function __construct(
        public AuthenticatedUser $user,
        /** Returned to the client exactly once; only its digest is stored. */
        public string $sessionToken,
        public string $sessionId,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
