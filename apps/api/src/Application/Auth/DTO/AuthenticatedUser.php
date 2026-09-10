<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\DTO;

use Yume\Api\Domain\User\Entity\User;

/** The read model returned to the client. Never carries a hash or a token. */
final readonly class AuthenticatedUser
{
    /**
     * @param list<string> $roles
     * @param list<string> $permissions
     */
    public function __construct(
        public string $id,
        public string $username,
        public string $email,
        public string $status,
        public bool $emailVerified,
        public array $roles,
        public array $permissions,
        public string $createdAt,
    ) {
    }

    /** @param list<string> $permissions */
    public static function fromEntity(User $user, array $permissions): self
    {
        return new self(
            $user->id->value,
            $user->username()->value,
            $user->email()->value,
            $user->status()->value,
            $user->isEmailVerified(),
            $user->roles(),
            $permissions,
            $user->createdAt->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'status' => $this->status,
            'email_verified' => $this->emailVerified,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'created_at' => $this->createdAt,
        ];
    }
}
