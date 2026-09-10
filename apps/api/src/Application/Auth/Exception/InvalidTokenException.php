<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

/**
 * One message for missing, expired, already-used and forged tokens alike.
 *
 * Distinguishing them would tell an attacker whether a guessed token ever
 * existed, and whether an account has a reset in flight.
 */
final class InvalidTokenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This link is invalid or has expired. Request a new one.');
    }

    public function errorCode(): string
    {
        return 'auth.invalid_token';
    }

    public function httpStatus(): int
    {
        return 400;
    }
}
