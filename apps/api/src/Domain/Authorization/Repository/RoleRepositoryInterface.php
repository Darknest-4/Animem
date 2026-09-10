<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Authorization\Repository;

use Yume\Api\Domain\Authorization\Entity\Role;
use Yume\Api\Domain\Authorization\ValueObject\PermissionSet;
use Yume\Api\Domain\User\ValueObject\UserId;

interface RoleRepositoryInterface
{
    public function findBySlug(string $slug): ?Role;

    /** @return list<Role> */
    public function all(): array;

    /** Effective permissions for a signed-in user, flattened across their roles. */
    public function permissionsForUser(UserId $userId): PermissionSet;

    /** Effective permissions for an anonymous visitor (the `guest` role). */
    public function permissionsForGuest(): PermissionSet;

    /** @return list<string> role slugs */
    public function roleSlugsForUser(UserId $userId): array;

    public function assignRole(UserId $userId, string $roleSlug, ?UserId $grantedBy = null): void;

    public function revokeRole(UserId $userId, string $roleSlug): void;
}
