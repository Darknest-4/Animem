<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Audit;

use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\User\ValueObject\UserId;

interface SecurityAuditRepositoryInterface
{
    public function record(SecurityEvent $event): void;

    public function countRecentFailedLogins(IpAddress $ip, \DateTimeImmutable $since): int;

    /** @return list<SecurityEvent> */
    public function recentForUser(UserId $userId, int $limit = 50): array;

    /** @return list<SecurityEvent> */
    public function recentBySeverity(string $severity, int $limit = 100): array;
}
