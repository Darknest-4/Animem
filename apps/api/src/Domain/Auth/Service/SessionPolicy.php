<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Service;

use Yume\Api\Domain\Auth\Entity\Session;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;

/** Decides how long a session lives and when it must be re-hardened. */
final readonly class SessionPolicy
{
    public function __construct(
        public int $absoluteLifetimeSeconds = 2592000,   // 30 days
        public int $idleExtensionSeconds = 1209600,      // 14 days sliding window
        public int $rotateAfterSeconds = 3600,           // rotate the token hourly
        public int $maxConcurrentSessions = 10,
    ) {
    }

    public function shouldRotateToken(Session $session, \DateTimeImmutable $now): bool
    {
        return $session->lastSeenAt()->modify(sprintf('+%d seconds', $this->rotateAfterSeconds)) <= $now;
    }

    /**
     * A session whose client fingerprint changed wholesale is treated as stolen.
     * IP changes alone are normal (mobile networks), so only the user agent is decisive.
     */
    public function looksHijacked(Session $session, IpAddress $ip, UserAgent $userAgent): bool
    {
        if ($session->createdUserAgent->isEmpty() || $userAgent->isEmpty()) {
            return false;
        }

        return $session->createdUserAgent->value !== $userAgent->value;
    }
}
