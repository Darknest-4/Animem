<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
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
