<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class WeakPasswordException extends DomainException
{
    private function __construct(string $message, private readonly string $reason)
    {
        parent::__construct($message);
    }

    public static function tooShort(int $minimum): self
    {
        return new self(
            sprintf('Password must be at least %d characters long.', $minimum),
            'too_short',
        );
    }

    public static function tooLong(int $maximum): self
    {
        return new self(
            sprintf('Password must not exceed %d characters.', $maximum),
            'too_long',
        );
    }

    public static function blocklisted(): self
    {
        return new self('That password is too common. Choose something less predictable.', 'blocklisted');
    }

    public static function containsPersonalData(): self
    {
        return new self('Password must not contain your username or email address.', 'personal_data');
    }

    public static function tooPredictable(): self
    {
        return new self('That password is too predictable.', 'predictable');
    }

    public function errorCode(): string
    {
        return 'auth.weak_password';
    }

    public function context(): array
    {
        return ['reason' => $this->reason];
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
