<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class LogoutCommand implements CommandInterface
{
    public function __construct(
        public string $sessionId,
        public string $ip,
        public string $userAgent,
    ) {
    }
}
