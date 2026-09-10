<?php

declare(strict_types=1);

namespace Yume\Api\Domain\User\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class UsernameAlreadyUsedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('That username is already taken.');
    }

    public function errorCode(): string
    {
        return 'user.username_taken';
    }

    public function httpStatus(): int
    {
        return 409;
    }
}
