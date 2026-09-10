<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\DTO\AuthenticatedUser;
use Yume\Api\Application\Auth\Query\GetCurrentUserQuery;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\User\Exception\UserNotFoundException;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\UserId;

final class GetCurrentUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
    ) {
    }

    public function __invoke(GetCurrentUserQuery $query): AuthenticatedUser
    {
        $userId = UserId::fromString($query->userId);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::withId($query->userId);
        }

        return AuthenticatedUser::fromEntity(
            $user,
            $this->roles->permissionsForUser($userId)->toStrings(),
        );
    }
}
