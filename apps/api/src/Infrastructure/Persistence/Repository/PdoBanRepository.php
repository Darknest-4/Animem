<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Security\Ban\Ban;
use Yume\Api\Domain\Security\Ban\BanRepositoryInterface;
use Yume\Api\Domain\Security\Ban\BanScope;
use Yume\Api\Domain\Security\Ban\BanType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Persistence\ConnectionInterface;

final class PdoBanRepository implements BanRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function findActiveFor(IpAddress $ip, ?UserId $userId, \DateTimeImmutable $now): ?Ban
    {
        // All four scopes in one round trip. A manual ban outranks an automatic
        // one of the same age, so an operator decision is never masked by a
        // shorter machine-issued block.
        $row = $this->connection->selectOne(
            <<<'SQL'
            SELECT id, scope, ban_type, subject, reason, created_at, expires_at, created_by
            FROM bans
            WHERE lifted_at IS NULL
              AND (expires_at IS NULL OR expires_at > :now)
              AND (
                    scope = 'global'
                 OR (scope = 'ip'     AND subject = :ip)
                 OR (scope = 'subnet' AND subject = :subnet)
                 OR (scope = 'user'   AND subject = :user_id AND :user_id <> '')
              )
            ORDER BY CASE ban_type WHEN 'manual' THEN 0 ELSE 1 END,
                     (expires_at IS NULL) DESC,
                     created_at DESC
            LIMIT 1
            SQL,
            [
                'now' => $now->format('Y-m-d H:i:sP'),
                'ip' => $ip->value,
                'subnet' => $ip->subnetKey(),
                'user_id' => $userId?->value ?? '',
            ],
        );

        return $row === null ? null : self::hydrate($row);
    }

    public function save(Ban $ban): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO bans (id, scope, ban_type, subject, reason, created_at, expires_at, created_by)
            VALUES (:id, :scope, :ban_type, :subject, :reason, :created_at, :expires_at, :created_by)
            ON CONFLICT (id) DO UPDATE SET
                reason     = EXCLUDED.reason,
                expires_at = EXCLUDED.expires_at
            SQL,
            [
                'id' => $ban->id,
                'scope' => $ban->scope->value,
                'ban_type' => $ban->type->value,
                'subject' => $ban->subject,
                'reason' => $ban->reason,
                'created_at' => $ban->createdAt->format('Y-m-d H:i:sP'),
                'expires_at' => $ban->expiresAt?->format('Y-m-d H:i:sP'),
                'created_by' => $ban->createdBy,
            ],
        );
    }

    public function lift(string $banId, \DateTimeImmutable $now): void
    {
        $this->connection->execute(
            'UPDATE bans SET lifted_at = :now WHERE id = :id AND lifted_at IS NULL',
            ['id' => $banId, 'now' => $now->format('Y-m-d H:i:sP')],
        );
    }

    public function listActive(\DateTimeImmutable $now, int $limit = 100): array
    {
        $rows = $this->connection->select(
            <<<'SQL'
            SELECT id, scope, ban_type, subject, reason, created_at, expires_at, created_by
            FROM bans
            WHERE lifted_at IS NULL AND (expires_at IS NULL OR expires_at > :now)
            ORDER BY created_at DESC
            LIMIT :limit
            SQL,
            ['now' => $now->format('Y-m-d H:i:sP'), 'limit' => max(1, min(500, $limit))],
        );

        return array_map(self::hydrate(...), $rows);
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): Ban
    {
        return new Ban(
            (string) $row['id'],
            BanScope::from((string) $row['scope']),
            BanType::from((string) $row['ban_type']),
            is_string($row['subject'] ?? null) ? (string) $row['subject'] : null,
            (string) $row['reason'],
            new \DateTimeImmutable((string) $row['created_at']),
            is_string($row['expires_at'] ?? null) ? new \DateTimeImmutable((string) $row['expires_at']) : null,
            is_string($row['created_by'] ?? null) ? (string) $row['created_by'] : null,
        );
    }
}
