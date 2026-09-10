<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\ValueObject;

enum TokenPurpose: string
{
    case EmailVerification = 'email_verification';
    case PasswordReset = 'password_reset';

    /** How long a freshly issued token stays usable. */
    public function lifetimeSeconds(): int
    {
        return match ($this) {
            // Generous: people open confirmation mail the next morning.
            self::EmailVerification => 86400,
            // Deliberately short. A reset link is a full account takeover if it
            // leaks from a mailbox, a browser history or a shared screen.
            self::PasswordReset => 3600,
        };
    }
}
