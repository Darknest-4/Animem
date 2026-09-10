<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\User\Entity\User;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\Email;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Domain\User\ValueObject\Username;
use Yume\Api\Domain\User\ValueObject\UserStatus;
use Yume\Api\Infrastructure\Persistence\PostgreSQL\PgArray;
use Yume\Contracts\Persistence\ConnectionInterface;

final class PdoUserRepository implements UserRepositoryInterface
{
    private const SELECT = <<<'SQL'
        SELECT u.id, u.username, u.email, u.status, u.email_verified_at,
               u.created_at, u.updated_at,
               COALESCE(
                   (SELECT array_agg(r.slug ORDER BY r.slug)
                    FROM user_roles ur JOIN roles r ON r.id = ur.role_id
                    WHERE ur.user_id = u.id),
                   ARRAY[]::varchar[]
               ) AS role_slugs
        FROM users u
        SQL;

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function findById(UserId $id): ?User
    {
        $row = $this->connection->selectOne(self::SELECT . ' WHERE u.id = :id', ['id' => $id->value]);

        return $row === null ? null : self::hydrate($row);
    }

    public function findByEmail(Email $email): ?User
    {
        $row = $this->connection->selectOne(
            self::SELECT . ' WHERE u.email_canonical = :email',
            ['email' => $email->canonical()],
        );

        return $row === null ? null : self::hydrate($row);
    }

    public function findByUsername(Username $username): ?User
    {
        $row = $this->connection->selectOne(
            self::SELECT . ' WHERE u.username_canonical = :username',
            ['username' => $username->canonical()],
        );

        return $row === null ? null : self::hydrate($row);
    }

    public function findByIdentifier(string $identifier): ?User
    {
        // One query for both shapes so the two branches cannot diverge in cost
        // and turn the login form into a "does this username exist" oracle.
        $canonicalUsername = null;
        $canonicalEmail = null;

        try {
            $canonicalUsername = Username::fromString($identifier)->canonical();
        } catch (\InvalidArgumentException) {
            // Not a syntactically valid username; fall through.
        }

        try {
            $canonicalEmail = Email::fromString($identifier)->canonical();
        } catch (\InvalidArgumentException) {
            // Not a syntactically valid email; fall through.
        }

        if ($canonicalUsername === null && $canonicalEmail === null) {
            return null;
        }

        $row = $this->connection->selectOne(
            self::SELECT . ' WHERE u.username_canonical = :username OR u.email_canonical = :email LIMIT 1',
            [
                'username' => $canonicalUsername ?? '',
                'email' => $canonicalEmail ?? '',
            ],
        );

        return $row === null ? null : self::hydrate($row);
    }

    public function emailExists(Email $email): bool
    {
        return (bool) $this->connection->scalar(
            'SELECT EXISTS (SELECT 1 FROM users WHERE email_canonical = :email)',
            ['email' => $email->canonical()],
        );
    }

    public function usernameExists(Username $username): bool
    {
        return (bool) $this->connection->scalar(
            'SELECT EXISTS (SELECT 1 FROM users WHERE username_canonical = :username)',
            ['username' => $username->canonical()],
        );
    }

    public function save(User $user): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO users (id, username, username_canonical, email, email_canonical,
                               status, email_verified_at, created_at, updated_at)
            VALUES (:id, :username, :username_canonical, :email, :email_canonical,
                    :status, :email_verified_at, :created_at, :updated_at)
            ON CONFLICT (id) DO UPDATE SET
                username           = EXCLUDED.username,
                username_canonical = EXCLUDED.username_canonical,
                email              = EXCLUDED.email,
                email_canonical    = EXCLUDED.email_canonical,
                status             = EXCLUDED.status,
                email_verified_at  = EXCLUDED.email_verified_at,
                updated_at         = EXCLUDED.updated_at
            SQL,
            [
                'id' => $user->id->value,
                'username' => $user->username()->value,
                'username_canonical' => $user->username()->canonical(),
                'email' => $user->email()->value,
                'email_canonical' => $user->email()->canonical(),
                'status' => $user->status()->value,
                'email_verified_at' => $user->emailVerifiedAt()?->format('Y-m-d H:i:sP'),
                'created_at' => $user->createdAt->format('Y-m-d H:i:sP'),
                'updated_at' => $user->updatedAt()->format('Y-m-d H:i:sP'),
            ],
        );
    }

    public function paginate(int $limit, int $offset): array
    {
        $rows = $this->connection->select(
            self::SELECT . ' ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset',
            ['limit' => max(1, min(100, $limit)), 'offset' => max(0, $offset)],
        );

        return array_map(self::hydrate(...), $rows);
    }

    public function count(): int
    {
        return (int) $this->connection->scalar('SELECT count(*) FROM users');
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): User
    {
        return User::reconstitute(
            UserId::fromString((string) $row['id']),
            Username::fromString((string) $row['username']),
            Email::fromString((string) $row['email']),
            UserStatus::from((string) $row['status']),
            self::toDate($row['email_verified_at'] ?? null),
            PgArray::toStrings($row['role_slugs'] ?? null),
            self::toDate($row['created_at']) ?? new \DateTimeImmutable(),
            self::toDate($row['updated_at']) ?? new \DateTimeImmutable(),
        );
    }

    private static function toDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return new \DateTimeImmutable($value);
    }
}
