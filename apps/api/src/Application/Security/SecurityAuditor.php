<?php

declare(strict_types=1);

namespace Yume\Api\Application\Security;

use Yume\Api\Domain\Security\Audit\SecurityAuditRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityEvent;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;
use Yume\Contracts\Logging\LoggerInterface;

/**
 * Writes to the audit trail and mirrors the record into structured logs.
 *
 * Auditing must never take down the request it is describing, so a repository
 * failure is logged and swallowed. Losing one audit row is bad; refusing a
 * legitimate login because the audit table is full is worse.
 */
final class SecurityAuditor
{
    public function __construct(
        private readonly SecurityAuditRepositoryInterface $repository,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @param array<string, mixed> $metadata */
    public function record(
        SecurityEventType $type,
        IpAddress $ip,
        UserAgent $userAgent,
        string $method = '',
        string $path = '',
        ?UserId $userId = null,
        int $riskScore = 0,
        array $metadata = [],
    ): void {
        $event = new SecurityEvent(
            $this->ids->generate(),
            $type,
            $userId,
            $ip,
            $userAgent,
            $method,
            $path,
            $riskScore,
            $metadata,
            $this->clock->now(),
        );

        try {
            $this->repository->record($event);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to persist security event.', [
                'event_type' => $type->value,
                'exception' => $e->getMessage(),
            ]);
        }

        $message = 'security.' . $type->value;
        $context = [
            'user_id' => $userId?->value,
            'ip' => $ip->value,
            'path' => $path,
            'risk_score' => $riskScore,
            'metadata' => $metadata,
        ];

        match ($event->severity()) {
            'critical' => $this->logger->critical($message, $context),
            'warning', 'notice' => $this->logger->warning($message, $context),
            default => $this->logger->info($message, $context),
        };
    }
}
