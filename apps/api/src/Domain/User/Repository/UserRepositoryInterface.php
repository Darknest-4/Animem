<?php

declare(strict_types=1);

namespace Yume\Api\Domain\User\Repository;

use Yume\Api\Domain\User\Entity\User;
use Yume\Api\Domain\User\ValueObject\Email;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Domain\User\ValueObject\Username;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function findByUsername(Username $username): ?User;

    /** Accepts either an email address or a username, as typed at the login form. */
    public function findByIdentifier(string $identifier): ?User;

    public function emailExists(Email $email): bool;

    public function usernameExists(Username $username): bool;

    public function save(User $user): void;

    /** @return list<User> */
    public function paginate(int $limit, int $offset): array;

    public function count(): int;
}
