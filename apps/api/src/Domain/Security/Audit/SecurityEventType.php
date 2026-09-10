<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Audit;

enum SecurityEventType: string
{
    case LoginSucceeded = 'login.succeeded';
    case LoginFailed = 'login.failed';
    case LoginBlocked = 'login.blocked';
    case Logout = 'logout';
    case Registered = 'user.registered';
    case PasswordChanged = 'password.changed';
    case PasswordUpgraded = 'password.upgraded';
    case SessionRevoked = 'session.revoked';
    case SessionHijackSuspected = 'session.hijack_suspected';
    case AuthorizationDenied = 'authorization.denied';
    case RateLimited = 'rate_limit.exceeded';
    case RiskBlocked = 'risk.blocked';
    case RiskChallenged = 'risk.challenged';
    case BanApplied = 'ban.applied';
    case BanLifted = 'ban.lifted';
    case RoleAssigned = 'role.assigned';
    case RoleRevoked = 'role.revoked';
    case FeatureFlagChanged = 'feature_flag.changed';

    public function severity(): string
    {
        return match ($this) {
            self::LoginSucceeded, self::Logout, self::Registered => 'info',
            self::LoginFailed, self::RateLimited, self::AuthorizationDenied => 'notice',
            self::LoginBlocked, self::RiskChallenged, self::SessionRevoked,
            self::PasswordChanged, self::PasswordUpgraded, self::RoleAssigned,
            self::RoleRevoked, self::FeatureFlagChanged, self::BanLifted => 'warning',
            self::RiskBlocked, self::BanApplied, self::SessionHijackSuspected => 'critical',
        };
    }
}
