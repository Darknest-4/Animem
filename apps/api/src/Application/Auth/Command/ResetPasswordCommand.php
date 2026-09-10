<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class ResetPasswordCommand implements CommandInterface
{
    public function __construct(
        public string $token,
        public string $newPassword,
        public string $ip,
        public string $userAgent,
    ) {
    }
}
