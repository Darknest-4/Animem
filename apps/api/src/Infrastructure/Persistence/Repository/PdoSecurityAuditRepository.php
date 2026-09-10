<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Security\Audit\SecurityAuditRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityEvent;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Shared\Support\Json;

final class PdoSecurityAuditRepository implements SecurityAuditRepositoryInterface
{
    private const SELECT = <<<'SQL'
        SELECT id, event_type, user_id, ip, user_agent, method, path,
               risk_score, metadata, occurred_at
        FROM security_events
        SQL;

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function record(SecurityEvent $event): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO security_events (id, event_type, severity, user_id, ip, user_agent,
                                         method, path, risk_score, metadata, occurred_at)
            VALUES (:id, :event_type, :severity, :user_id, :ip, :user_agent,
                    :method, :path, :risk_score, CAST(:metadata AS jsonb), :occurred_at)
            SQL,
            [
                'id' => $event->id,
                'event_type' => $event->type->value,
                'severity' => $event->severity(),
                'user_id' => $event->userId?->value,
                'ip' => $event->ip->value,
                'user_agent' => $event->userAgent->value,
                'method' => $event->method,
                'path' => mb_substr($event->path, 0, 512),
                'risk_score' => $event->riskScore,
                'metadata' => Json::encode($event->metadata),
                'occurred_at' => $event->occurredAt->format('Y-m-d H:i:sP'),
            ],
        );
    }

    public function countRecentFailedLogins(IpAddress $ip, \DateTimeImmutable $since): int
    {
        return (int) $this->connection->scalar(
            <<<'SQL'
            SELECT count(*)
            FROM security_events
            WHERE ip = :ip
              AND event_type = :event_type
              AND occurred_at >= :since
            SQL,
            [
                'ip' => $ip->value,
                'event_type' => SecurityEventType::LoginFailed->value,
                'since' => $since->format('Y-m-d H:i:sP'),
            ],
        );
    }

    public function recentForUser(UserId $userId, int $limit = 50): array
    {
        $rows = $this->connection->select(
            self::SELECT . ' WHERE user_id = :user_id ORDER BY occurred_at DESC LIMIT :limit',
            ['user_id' => $userId->value, 'limit' => max(1, min(200, $limit))],
        );

        return array_map(self::hydrate(...), $rows);
    }

    public function recentBySeverity(string $severity, int $limit = 100): array
    {
        $rows = $this->connection->select(
            self::SELECT . ' WHERE severity = :severity ORDER BY occurred_at DESC LIMIT :limit',
            ['severity' => $severity, 'limit' => max(1, min(500, $limit))],
        );

        return array_map(self::hydrate(...), $rows);
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): SecurityEvent
    {
        return new SecurityEvent(
            (string) $row['id'],
            SecurityEventType::from((string) $row['event_type']),
            is_string($row['user_id'] ?? null) ? UserId::fromString((string) $row['user_id']) : null,
            IpAddress::fromString((string) $row['ip']),
            UserAgent::fromString((string) $row['user_agent']),
            (string) $row['method'],
            (string) $row['path'],
            (int) $row['risk_score'],
            Json::decodeToArrayOrEmpty((string) ($row['metadata'] ?? '{}')),
            new \DateTimeImmutable((string) $row['occurred_at']),
        );
    }
}
