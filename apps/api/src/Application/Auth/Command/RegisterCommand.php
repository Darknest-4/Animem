<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class RegisterCommand implements CommandInterface
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
        public string $ip,
        public string $userAgent,
        public bool $acceptedTerms,
    ) {
    }
}
