<?php

declare(strict_types=1);

namespace Yume\Api\Domain\User\ValueObject;

enum UserStatus: string
{
    case PendingVerification = 'pending_verification';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function canAuthenticate(): bool
    {
        return $this === self::Active || $this === self::PendingVerification;
    }

    public function canAccessProtectedResources(): bool
    {
        return $this === self::Active;
    }
}
