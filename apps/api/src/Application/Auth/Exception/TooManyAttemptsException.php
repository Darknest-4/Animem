<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class TooManyAttemptsException extends DomainException
{
    public function __construct(private readonly int $retryAfterSeconds)
    {
        parent::__construct('Too many attempts. Please slow down.');
    }

    public function retryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }

    public function errorCode(): string
    {
        return 'auth.too_many_attempts';
    }

    public function context(): array
    {
        return ['retry_after' => $this->retryAfterSeconds];
    }

    public function httpStatus(): int
    {
        return 429;
    }
}
