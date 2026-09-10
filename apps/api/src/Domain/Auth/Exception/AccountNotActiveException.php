<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class AccountNotActiveException extends DomainException
{
    public function __construct(string $message = 'This account is not active.')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'auth.account_not_active';
    }

    public function httpStatus(): int
    {
        return 403;
    }
}
