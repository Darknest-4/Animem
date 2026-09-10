<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class RevokeSessionCommand implements CommandInterface
{
    public function __construct(
        public string $actingUserId,
        public string $targetSessionId,
        public string $ip,
        public string $userAgent,
        /** Revoke every session of the acting user instead of a single one. */
        public bool $allSessions = false,
    ) {
    }
}
