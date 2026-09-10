<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class ChangePasswordCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public string $currentPassword,
        public string $newPassword,
        public string $currentSessionId,
        public string $ip,
        public string $userAgent,
    ) {
    }
}
