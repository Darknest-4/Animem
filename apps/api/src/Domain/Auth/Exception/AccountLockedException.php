<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class AccountLockedException extends DomainException
{
    public function __construct(string $message = 'Too many failed attempts. Try again later.')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'auth.account_locked';
    }

    public function httpStatus(): int
    {
        return 423;
    }
}
