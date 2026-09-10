<?php

declare(strict_types=1);

namespace Yume\Api\Domain\User\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class EmailAlreadyUsedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('That email address is already registered.');
    }

    public function errorCode(): string
    {
        return 'user.email_taken';
    }

    public function httpStatus(): int
    {
        return 409;
    }
}
