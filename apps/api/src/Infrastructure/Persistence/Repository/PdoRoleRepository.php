<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Authorization\Entity\Role;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\Authorization\ValueObject\PermissionSet;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Persistence\ConnectionInterface;

final class PdoRoleRepository implements RoleRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function findBySlug(string $slug): ?Role
    {
        $row = $this->connection->selectOne(
            'SELECT id, slug, name, is_system FROM roles WHERE slug = :slug',
            ['slug' => $slug],
        );

        if ($row === null) {
            return null;
        }

        return new Role(
            (string) $row['slug'],
            (string) $row['name'],
            $this->permissionsForRoleId((int) $row['id']),
            (bool) $row['is_system'],
        );
    }

    public function all(): array
    {
        $rows = $this->connection->select('SELECT id, slug, name, is_system FROM roles ORDER BY slug');

        return array_map(
            fn (array $row): Role => new Role(
                (string) $row['slug'],
                (string) $row['name'],
                $this->permissionsForRoleId((int) $row['id']),
                (bool) $row['is_system'],
            ),
            $rows,
        );
    }

    public function permissionsForUser(UserId $userId): PermissionSet
    {
        // One flattening join, not the legacy perm()'s N+1 loop of three nested
        // queries per permission check.
        $rows = $this->connection->select(
            <<<'SQL'
            SELECT DISTINCT p.slug
            FROM user_roles ur
                JOIN role_permissions rp ON rp.role_id = ur.role_id
                JOIN permissions p       ON p.id = rp.permission_id
            WHERE ur.user_id = :user_id
            SQL,
            ['user_id' => $userId->value],
        );

        return PermissionSet::fromStrings(array_map(
            static fn (array $row): string => (string) $row['slug'],
            $rows,
        ));
    }

    public function permissionsForGuest(): PermissionSet
    {
        $rows = $this->connection->select(
            <<<'SQL'
            SELECT p.slug
            FROM roles r
                JOIN role_permissions rp ON rp.role_id = r.id
                JOIN permissions p       ON p.id = rp.permission_id
            WHERE r.slug = 'guest'
            SQL,
        );

        return PermissionSet::fromStrings(array_map(
            static fn (array $row): string => (string) $row['slug'],
            $rows,
        ));
    }

    public function roleSlugsForUser(UserId $userId): array
    {
        $rows = $this->connection->select(
            <<<'SQL'
            SELECT r.slug
            FROM user_roles ur JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
            ORDER BY r.slug
            SQL,
            ['user_id' => $userId->value],
        );

        return array_map(static fn (array $row): string => (string) $row['slug'], $rows);
    }

    public function assignRole(UserId $userId, string $roleSlug, ?UserId $grantedBy = null): void
    {
        $affected = $this->connection->execute(
            <<<'SQL'
            INSERT INTO user_roles (user_id, role_id, granted_by)
            SELECT :user_id, r.id, :granted_by FROM roles r WHERE r.slug = :slug
            ON CONFLICT (user_id, role_id) DO NOTHING
            SQL,
            [
                'user_id' => $userId->value,
                'slug' => $roleSlug,
                'granted_by' => $grantedBy?->value,
            ],
        );

        // A silently missing role would leave the account with no permissions at
        // all, which is exactly the kind of failure that should be loud.
        if ($affected === 0 && !$this->userHasRole($userId, $roleSlug)) {
            throw new \RuntimeException(sprintf('Role "%s" does not exist.', $roleSlug));
        }
    }

    public function revokeRole(UserId $userId, string $roleSlug): void
    {
        $this->connection->execute(
            <<<'SQL'
            DELETE FROM user_roles
            WHERE user_id = :user_id
              AND role_id = (SELECT id FROM roles WHERE slug = :slug)
            SQL,
            ['user_id' => $userId->value, 'slug' => $roleSlug],
        );
    }

    private function userHasRole(UserId $userId, string $roleSlug): bool
    {
        return (bool) $this->connection->scalar(
            <<<'SQL'
            SELECT EXISTS (
                SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id
                WHERE ur.user_id = :user_id AND r.slug = :slug
            )
            SQL,
            ['user_id' => $userId->value, 'slug' => $roleSlug],
        );
    }

    private function permissionsForRoleId(int $roleId): PermissionSet
    {
        $rows = $this->connection->select(
            <<<'SQL'
            SELECT p.slug
            FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id
            ORDER BY p.slug
            SQL,
            ['role_id' => $roleId],
        );

        return PermissionSet::fromStrings(array_map(
            static fn (array $row): string => (string) $row['slug'],
            $rows,
        ));
    }
}
