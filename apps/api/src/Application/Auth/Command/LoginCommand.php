<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class LoginCommand implements CommandInterface
{
    public function __construct(
        /** Username or email address, as typed. */
        public string $identifier,
        public string $password,
        public string $ip,
        public string $userAgent,
    ) {
    }
}
