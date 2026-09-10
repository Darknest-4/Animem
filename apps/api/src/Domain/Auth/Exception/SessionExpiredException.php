<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class SessionExpiredException extends DomainException
{
    public function __construct(string $message = 'Your session has expired.')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'auth.session_expired';
    }

    public function httpStatus(): int
    {
        return 401;
    }
}
