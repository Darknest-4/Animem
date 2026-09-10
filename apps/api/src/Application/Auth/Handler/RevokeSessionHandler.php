<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\RevokeSessionCommand;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\ValueObject\SessionId;
use Yume\Api\Domain\Authorization\Exception\AccessDeniedException;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;

/** "Sign out my other devices" — plus the ownership check that makes it safe. */
final class RevokeSessionHandler
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly SecurityAuditor $auditor,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(RevokeSessionCommand $command): int
    {
        $actingUserId = UserId::fromString($command->actingUserId);
        $ip = IpAddress::fromString($command->ip);
        $userAgent = UserAgent::fromString($command->userAgent);
        $now = $this->clock->now();

        if ($command->allSessions) {
            $revoked = $this->sessions->revokeAllForUser($actingUserId, $now, 'user_revoked_all');

            $this->auditor->record(
                SecurityEventType::SessionRevoked,
                $ip,
                $userAgent,
                'DELETE',
                '/api/v1/auth/sessions',
                $actingUserId,
                0,
                ['scope' => 'all', 'revoked_count' => $revoked],
            );

            return $revoked;
        }

        $session = $this->sessions->findById(SessionId::fromString($command->targetSessionId));

        if ($session === null) {
            return 0;
        }

        // A user may only revoke their own sessions; anything else is an
        // authorization decision, not a 404, and is audited as such.
        if (!$session->userId->equals($actingUserId)) {
            $this->auditor->record(
                SecurityEventType::AuthorizationDenied,
                $ip,
                $userAgent,
                'DELETE',
                '/api/v1/auth/sessions',
                $actingUserId,
                0,
                ['reason' => 'session_owned_by_another_user'],
            );

            throw AccessDeniedException::missingPermission('session.revoke_own');
        }

        $session->revoke($now, 'user_revoked');
        $this->sessions->save($session);

        $this->auditor->record(
            SecurityEventType::SessionRevoked,
            $ip,
            $userAgent,
            'DELETE',
            '/api/v1/auth/sessions',
            $actingUserId,
            0,
            ['scope' => 'one', 'session_id' => $session->id->value],
        );

        return 1;
    }
}
