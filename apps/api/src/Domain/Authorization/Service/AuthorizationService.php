<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Authorization\Service;

use Yume\Api\Domain\Authorization\Exception\AccessDeniedException;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\Authorization\ValueObject\PermissionSet;
use Yume\Api\Domain\User\ValueObject\UserId;

/**
 * The single place that answers "may this actor do this?".
 *
 * Templates may call {@see allows()} to hide a button, but hiding is never the
 * defence: the route's declared permission is enforced by AuthorizeMiddleware
 * before a controller runs. The legacy `perm()` function was called from views
 * only, which meant every admin action could be reached by typing the URL.
 */
final class AuthorizationService
{
    /** @var array<string, PermissionSet> memoised per request */
    private array $cache = [];

    public function __construct(private readonly RoleRepositoryInterface $roles)
    {
    }

    public function permissionsFor(?UserId $userId): PermissionSet
    {
        $key = $userId?->value ?? '@guest';

        return $this->cache[$key] ??= $userId === null
            ? $this->roles->permissionsForGuest()
            : $this->roles->permissionsForUser($userId);
    }

    public function allows(?UserId $userId, string $permission): bool
    {
        return $this->permissionsFor($userId)->allows($permission);
    }

    /** @throws AccessDeniedException */
    public function assert(?UserId $userId, string $permission): void
    {
        if (!$this->allows($userId, $permission)) {
            throw AccessDeniedException::missingPermission($permission);
        }
    }

    public function forgetUser(UserId $userId): void
    {
        unset($this->cache[$userId->value]);
    }
}
