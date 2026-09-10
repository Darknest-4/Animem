<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\Exception\UserNotFoundException;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Api\Domain\User\Entity\User;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Presentation\Http\Request\Validator;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * Administrative user listing.
 *
 * Note what is absent: any permission check. The route declares
 * ->can('user.view') and AuthorizeMiddleware enforces it before this class is
 * constructed, so a check here would be duplication that can drift out of sync.
 */
final class AdminUserController
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
        private readonly SessionRepositoryInterface $sessions,
        private readonly SecurityAuditor $auditor,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function index(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $query = Validator::for([
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', 25),
        ])
            ->integer('page', 1, 10000)
            ->integer('per_page', 1, 100)
            ->validated();

        $page = (int) $query['page'];
        $perPage = (int) $query['per_page'];

        $users = $this->users->paginate($perPage, ($page - 1) * $perPage);
        $total = $this->users->count();

        return JsonResponse::ok([
            'users' => array_map($this->present(...), $users),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Suspend or reinstate an account.
     *
     * Suspension revokes every session immediately — leaving the suspended user
     * signed in until their cookie expires would make the action decorative.
     *
     * @param array<string, string> $parameters
     */
    public function setStatus(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $targetId = UserId::fromString($parameters['id'] ?? '');
        $user = $this->users->findById($targetId);

        if ($user === null) {
            throw UserNotFoundException::withId($targetId->value);
        }

        $data = Validator::for($request->body())->boolean('suspended')->validated();
        $suspend = (bool) $data['suspended'];

        $rawActing = $request->attribute('user_id');
        $actingUserId = is_string($rawActing) ? UserId::fromString($rawActing) : null;

        if ($suspend && $actingUserId !== null && $actingUserId->equals($targetId)) {
            return ProblemDetails::make(422, 'user.self_suspension_refused', 'You cannot suspend your own account.');
        }

        // Refusing to suspend the last admin, for the same reason as role revocation.
        if ($suspend && $user->hasRole('admin') && $this->roles->countUsersWithRole('admin') <= 1) {
            return ProblemDetails::make(
                422,
                'user.last_admin',
                'This is the last admin account. Grant the role to someone else first.',
            );
        }

        $now = $this->clock->now();
        $revoked = 0;

        if ($suspend) {
            $user->suspend($now);
            $revoked = $this->sessions->revokeAllForUser($targetId, $now, 'account_suspended');
        } else {
            $user->reinstate($now);
        }

        $this->users->save($user);

        $this->auditor->record(
            $suspend ? SecurityEventType::UserSuspended : SecurityEventType::UserReinstated,
            IpAddress::fromString($request->ip()),
            UserAgent::fromString($request->userAgent()),
            $request->method(),
            $request->path(),
            $actingUserId,
            0,
            [
                'event' => $suspend ? 'user_suspended' : 'user_reinstated',
                'target_user_id' => $targetId->value,
                'sessions_revoked' => $revoked,
            ],
        );

        return JsonResponse::ok([
            'user' => $this->present($user),
            'sessions_revoked' => $revoked,
        ]);
    }

    /** @return array<string, mixed> */
    private function present(User $user): array
    {
        return [
            'id' => $user->id->value,
            'username' => $user->username()->value,
            'email' => $user->email()->value,
            'status' => $user->status()->value,
            'email_verified' => $user->isEmailVerified(),
            'roles' => $user->roles(),
            'created_at' => $user->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
