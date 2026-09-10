<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Auth\Entity\Credential;
use Yume\Api\Domain\Auth\Repository\CredentialRepositoryInterface;
use Yume\Api\Domain\Auth\ValueObject\PasswordAlgorithm;
use Yume\Api\Domain\Auth\ValueObject\PasswordHash;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Persistence\ConnectionInterface;

final class PdoCredentialRepository implements CredentialRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly ClockInterface $clock,
    ) {
    }

    public function findForUser(UserId $userId): ?Credential
    {
        $row = $this->connection->selectOne(
            <<<'SQL'
            SELECT user_id, password_hash, password_algorithm, password_changed_at,
                   failed_attempts, locked_until
            FROM user_credentials
            WHERE user_id = :user_id
            SQL,
            ['user_id' => $userId->value],
        );

        if ($row === null) {
            return null;
        }

        return Credential::reconstitute(
            UserId::fromString((string) $row['user_id']),
            PasswordHash::fromStorage(
                (string) $row['password_hash'],
                PasswordAlgorithm::from((string) $row['password_algorithm']),
            ),
            self::toDate($row['password_changed_at']),
            (int) $row['failed_attempts'],
            self::toDate($row['locked_until']),
        );
    }

    public function save(Credential $credential): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO user_credentials (user_id, password_hash, password_algorithm,
                                          password_changed_at, failed_attempts, locked_until, updated_at)
            VALUES (:user_id, :password_hash, :password_algorithm,
                    :password_changed_at, :failed_attempts, :locked_until, :updated_at)
            ON CONFLICT (user_id) DO UPDATE SET
                password_hash       = EXCLUDED.password_hash,
                password_algorithm  = EXCLUDED.password_algorithm,
                password_changed_at = EXCLUDED.password_changed_at,
                failed_attempts     = EXCLUDED.failed_attempts,
                locked_until        = EXCLUDED.locked_until,
                updated_at          = EXCLUDED.updated_at
            SQL,
            [
                'user_id' => $credential->userId->value,
                'password_hash' => $credential->passwordHash()->value,
                'password_algorithm' => $credential->passwordHash()->algorithm->value,
                'password_changed_at' => $credential->passwordChangedAt()?->format('Y-m-d H:i:sP'),
                'failed_attempts' => $credential->failedAttempts(),
                'locked_until' => $credential->lockedUntil()?->format('Y-m-d H:i:sP'),
                'updated_at' => $this->clock->now()->format('Y-m-d H:i:sP'),
            ],
        );
    }

    private static function toDate(mixed $value): ?\DateTimeImmutable
    {
        return is_string($value) && $value !== '' ? new \DateTimeImmutable($value) : null;
    }
}
