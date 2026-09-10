<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\LogoutCommand;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\ValueObject\SessionId;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Contracts\Clock\ClockInterface;

final class LogoutHandler
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly SecurityAuditor $auditor,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(LogoutCommand $command): bool
    {
        $session = $this->sessions->findById(SessionId::fromString($command->sessionId));

        // Idempotent: logging out twice is not an error worth surfacing.
        if ($session === null || $session->isRevoked()) {
            return true;
        }

        $now = $this->clock->now();
        $session->revoke($now, 'user_logout');
        $this->sessions->save($session);

        $this->auditor->record(
            SecurityEventType::Logout,
            IpAddress::fromString($command->ip),
            UserAgent::fromString($command->userAgent),
            'POST',
            '/api/v1/auth/logout',
            $session->userId,
            0,
            ['session_id' => $session->id->value],
        );

        return true;
    }
}
