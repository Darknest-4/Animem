<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Authorization\Entity\Role;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\Exception\UserNotFoundException;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Request\Validator;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * Role administration.
 *
 * Every grant and revocation is audited with the acting admin's id, because
 * "who made this person an admin, and when" is the first question asked after
 * an incident.
 */
final class AdminRoleController
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly UserRepositoryInterface $users,
        private readonly SecurityAuditor $auditor,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function index(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        return JsonResponse::ok([
            'roles' => array_map(
                static fn (Role $role): array => [
                    'slug' => $role->slug,
                    'name' => $role->name,
                    'is_system' => $role->isSystem,
                    'permissions' => $role->permissions->toStrings(),
                ],
                $this->roles->all(),
            ),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function grant(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $target = $this->resolveTarget($parameters);
        $data = Validator::for($request->body())->string('role', 2, 64)->validated();
        $roleSlug = (string) $data['role'];

        if ($this->roles->findBySlug($roleSlug) === null) {
            return ProblemDetails::make(404, 'role.not_found', sprintf('Role "%s" does not exist.', $roleSlug));
        }

        // 'guest' is the permission set for *anonymous* callers. Granting it to
        // an account would be meaningless, and quietly confusing.
        if ($roleSlug === 'guest') {
            return ProblemDetails::make(422, 'role.not_assignable', 'The guest role cannot be granted to an account.');
        }

        $actingUserId = $this->actingUserId($request);
        $this->roles->assignRole($target, $roleSlug, $actingUserId);

        $this->auditor->record(
            SecurityEventType::RoleAssigned,
            IpAddress::fromString($request->ip()),
            UserAgent::fromString($request->userAgent()),
            $request->method(),
            $request->path(),
            $actingUserId,
            0,
            ['target_user_id' => $target->value, 'role' => $roleSlug],
        );

        return JsonResponse::ok([
            'user_id' => $target->value,
            'roles' => $this->roles->roleSlugsForUser($target),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function revoke(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $target = $this->resolveTarget($parameters);
        $roleSlug = $parameters['role'] ?? '';
        $actingUserId = $this->actingUserId($request);

        // Removing your own admin role locks you out of the tool you are using.
        if ($roleSlug === 'admin' && $actingUserId !== null && $actingUserId->equals($target)) {
            return ProblemDetails::make(
                422,
                'role.self_revocation_refused',
                'You cannot revoke your own admin role. Ask another admin to do it.',
            );
        }

        if ($roleSlug === 'admin' && $this->roles->countUsersWithRole('admin') <= 1) {
            return ProblemDetails::make(
                422,
                'role.last_admin',
                'This is the last admin account. Grant the role to someone else first.',
            );
        }

        $this->roles->revokeRole($target, $roleSlug);

        $this->auditor->record(
            SecurityEventType::RoleRevoked,
            IpAddress::fromString($request->ip()),
            UserAgent::fromString($request->userAgent()),
            $request->method(),
            $request->path(),
            $actingUserId,
            0,
            ['target_user_id' => $target->value, 'role' => $roleSlug],
        );

        return JsonResponse::ok([
            'user_id' => $target->value,
            'roles' => $this->roles->roleSlugsForUser($target),
        ]);
    }

    /** @param array<string, string> $parameters */
    private function resolveTarget(array $parameters): UserId
    {
        $target = UserId::fromString($parameters['id'] ?? '');

        if ($this->users->findById($target) === null) {
            throw UserNotFoundException::withId($target->value);
        }

        return $target;
    }

    private function actingUserId(RequestInterface $request): ?UserId
    {
        $raw = $request->attribute('user_id');

        return is_string($raw) ? UserId::fromString($raw) : null;
    }
}
