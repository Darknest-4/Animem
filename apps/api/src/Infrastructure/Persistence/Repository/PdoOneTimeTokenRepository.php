<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Auth\Entity\OneTimeToken;
use Yume\Api\Domain\Auth\Repository\OneTimeTokenRepositoryInterface;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Auth\ValueObject\TokenPurpose;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Persistence\ConnectionInterface;

/**
 * Email-verification and password-reset tokens, one table per purpose.
 *
 * Every statement is written out in full per purpose rather than built by
 * interpolating a table name. Even though the name would come from a closed
 * enum and be safe, keeping "no SQL string in this codebase contains a
 * variable" absolute is worth more than the saved lines: it is an invariant a
 * reviewer can check by eye and a test can check mechanically
 * (tests/Security/SqlInjectionSurfaceTest.php).
 */
final class PdoOneTimeTokenRepository implements OneTimeTokenRepositoryInterface
{
    private const SELECT_EMAIL = <<<'SQL'
        SELECT id, user_id, token_hash, created_at, expires_at, consumed_at, NULL AS requested_ip
        FROM email_verification_tokens
        WHERE token_hash = :hash
        SQL;

    private const SELECT_RESET = <<<'SQL'
        SELECT id, user_id, token_hash, created_at, expires_at, consumed_at, requested_ip
        FROM password_reset_tokens
        WHERE token_hash = :hash
        SQL;

    private const INSERT_EMAIL = <<<'SQL'
        INSERT INTO email_verification_tokens (id, user_id, token_hash, created_at, expires_at, consumed_at)
        VALUES (:id, :user_id, :hash, :created_at, :expires_at, :consumed_at)
        ON CONFLICT (id) DO UPDATE SET consumed_at = EXCLUDED.consumed_at
        SQL;

    private const INSERT_RESET = <<<'SQL'
        INSERT INTO password_reset_tokens (id, user_id, token_hash, requested_ip, created_at, expires_at, consumed_at)
        VALUES (:id, :user_id, :hash, :ip, :created_at, :expires_at, :consumed_at)
        ON CONFLICT (id) DO UPDATE SET consumed_at = EXCLUDED.consumed_at
        SQL;

    private const CONSUME_ALL_EMAIL = <<<'SQL'
        UPDATE email_verification_tokens SET consumed_at = :now
        WHERE user_id = :user_id AND consumed_at IS NULL
        SQL;

    private const CONSUME_ALL_RESET = <<<'SQL'
        UPDATE password_reset_tokens SET consumed_at = :now
        WHERE user_id = :user_id AND consumed_at IS NULL
        SQL;

    private const PRUNE_EMAIL = 'DELETE FROM email_verification_tokens WHERE expires_at < :cutoff';

    private const PRUNE_RESET = 'DELETE FROM password_reset_tokens WHERE expires_at < :cutoff';

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function findByHash(TokenPurpose $purpose, TokenHash $hash): ?OneTimeToken
    {
        $row = $this->connection->selectOne(
            match ($purpose) {
                TokenPurpose::EmailVerification => self::SELECT_EMAIL,
                TokenPurpose::PasswordReset => self::SELECT_RESET,
            },
            ['hash' => $hash->value],
        );

        return $row === null ? null : self::hydrate($purpose, $row);
    }

    public function save(OneTimeToken $token): void
    {
        $common = [
            'id' => $token->id,
            'user_id' => $token->userId->value,
            'hash' => $token->tokenHash->value,
            'created_at' => $token->createdAt->format('Y-m-d H:i:sP'),
            'expires_at' => $token->expiresAt->format('Y-m-d H:i:sP'),
            'consumed_at' => $token->consumedAt()?->format('Y-m-d H:i:sP'),
        ];

        match ($token->purpose) {
            TokenPurpose::EmailVerification => $this->connection->execute(self::INSERT_EMAIL, $common),
            TokenPurpose::PasswordReset => $this->connection->execute(
                self::INSERT_RESET,
                [...$common, 'ip' => $token->requestedIp?->value],
            ),
        };
    }

    public function consumeAllForUser(UserId $userId, TokenPurpose $purpose, \DateTimeImmutable $now): int
    {
        return $this->connection->execute(
            match ($purpose) {
                TokenPurpose::EmailVerification => self::CONSUME_ALL_EMAIL,
                TokenPurpose::PasswordReset => self::CONSUME_ALL_RESET,
            },
            ['user_id' => $userId->value, 'now' => $now->format('Y-m-d H:i:sP')],
        );
    }

    public function deleteExpiredBefore(TokenPurpose $purpose, \DateTimeImmutable $cutoff): int
    {
        return $this->connection->execute(
            match ($purpose) {
                TokenPurpose::EmailVerification => self::PRUNE_EMAIL,
                TokenPurpose::PasswordReset => self::PRUNE_RESET,
            },
            ['cutoff' => $cutoff->format('Y-m-d H:i:sP')],
        );
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(TokenPurpose $purpose, array $row): OneTimeToken
    {
        return OneTimeToken::reconstitute(
            (string) $row['id'],
            UserId::fromString((string) $row['user_id']),
            $purpose,
            TokenHash::fromString((string) $row['token_hash']),
            IpAddress::tryFromString(is_string($row['requested_ip'] ?? null) ? (string) $row['requested_ip'] : null),
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['expires_at']),
            is_string($row['consumed_at'] ?? null) ? new \DateTimeImmutable((string) $row['consumed_at']) : null,
        );
    }
}
