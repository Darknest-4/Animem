<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class ResendVerificationCommand implements CommandInterface
{
    public function __construct(
        public string $email,
        public string $ip,
        public string $userAgent,
    ) {
    }
}
