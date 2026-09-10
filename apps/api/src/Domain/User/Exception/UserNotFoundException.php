<?php

declare(strict_types=1);

namespace Yume\Api\Domain\User\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class UserNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('User "%s" was not found.', $id));
    }

    public function errorCode(): string
    {
        return 'user.not_found';
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
