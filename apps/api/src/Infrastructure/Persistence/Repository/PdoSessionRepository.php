<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Auth\Entity\Session;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\ValueObject\SessionId;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Persistence\ConnectionInterface;

final class PdoSessionRepository implements SessionRepositoryInterface
{
    private const SELECT = <<<'SQL'
        SELECT id, user_id, token_hash, created_ip, created_user_agent,
               created_at, last_seen_at, expires_at, revoked_at, revoked_reason
        FROM sessions
        SQL;

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function findByTokenHash(TokenHash $hash): ?Session
    {
        $row = $this->connection->selectOne(
            self::SELECT . ' WHERE token_hash = :token_hash',
            ['token_hash' => $hash->value],
        );

        return $row === null ? null : self::hydrate($row);
    }

    public function findById(SessionId $id): ?Session
    {
        $row = $this->connection->selectOne(self::SELECT . ' WHERE id = :id', ['id' => $id->value]);

        return $row === null ? null : self::hydrate($row);
    }

    public function findActiveForUser(UserId $userId, \DateTimeImmutable $now): array
    {
        $rows = $this->connection->select(
            self::SELECT . <<<'SQL'
             WHERE user_id = :user_id
               AND revoked_at IS NULL
               AND expires_at > :now
             ORDER BY last_seen_at DESC
            SQL,
            ['user_id' => $userId->value, 'now' => $now->format('Y-m-d H:i:sP')],
        );

        return array_map(self::hydrate(...), $rows);
    }

    public function save(Session $session): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO sessions (id, user_id, token_hash, created_ip, created_user_agent,
                                  created_at, last_seen_at, expires_at, revoked_at, revoked_reason)
            VALUES (:id, :user_id, :token_hash, :created_ip, :created_user_agent,
                    :created_at, :last_seen_at, :expires_at, :revoked_at, :revoked_reason)
            ON CONFLICT (id) DO UPDATE SET
                token_hash     = EXCLUDED.token_hash,
                last_seen_at   = EXCLUDED.last_seen_at,
                expires_at     = EXCLUDED.expires_at,
                revoked_at     = EXCLUDED.revoked_at,
                revoked_reason = EXCLUDED.revoked_reason
            SQL,
            [
                'id' => $session->id->value,
                'user_id' => $session->userId->value,
                'token_hash' => $session->tokenHash()->value,
                'created_ip' => $session->createdIp->value,
                'created_user_agent' => $session->createdUserAgent->value,
                'created_at' => $session->createdAt->format('Y-m-d H:i:sP'),
                'last_seen_at' => $session->lastSeenAt()->format('Y-m-d H:i:sP'),
                'expires_at' => $session->expiresAt()->format('Y-m-d H:i:sP'),
                'revoked_at' => $session->revokedAt()?->format('Y-m-d H:i:sP'),
                'revoked_reason' => $session->revokedReason(),
            ],
        );
    }

    public function revokeAllForUser(UserId $userId, \DateTimeImmutable $now, string $reason): int
    {
        return $this->connection->execute(
            <<<'SQL'
            UPDATE sessions
            SET revoked_at = :now, revoked_reason = :reason
            WHERE user_id = :user_id AND revoked_at IS NULL
            SQL,
            [
                'user_id' => $userId->value,
                'now' => $now->format('Y-m-d H:i:sP'),
                'reason' => $reason,
            ],
        );
    }

    public function deleteExpiredBefore(\DateTimeImmutable $cutoff): int
    {
        return $this->connection->execute(
            'DELETE FROM sessions WHERE expires_at < :cutoff',
            ['cutoff' => $cutoff->format('Y-m-d H:i:sP')],
        );
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): Session
    {
        return Session::reconstitute(
            SessionId::fromString((string) $row['id']),
            UserId::fromString((string) $row['user_id']),
            TokenHash::fromString((string) $row['token_hash']),
            IpAddress::fromString((string) $row['created_ip']),
            UserAgent::fromString((string) $row['created_user_agent']),
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['last_seen_at']),
            new \DateTimeImmutable((string) $row['expires_at']),
            is_string($row['revoked_at'] ?? null) ? new \DateTimeImmutable((string) $row['revoked_at']) : null,
            is_string($row['revoked_reason'] ?? null) ? (string) $row['revoked_reason'] : null,
        );
    }
}
