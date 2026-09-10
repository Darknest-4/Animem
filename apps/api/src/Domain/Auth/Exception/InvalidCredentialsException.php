<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct(string $message = 'Invalid username or password.')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'auth.invalid_credentials';
    }

    public function httpStatus(): int
    {
        return 401;
    }
}
