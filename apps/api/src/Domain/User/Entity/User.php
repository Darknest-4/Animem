<?php

declare(strict_types=1);

namespace Yume\Api\Domain\User\Entity;

use Yume\Api\Domain\User\ValueObject\Email;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Domain\User\ValueObject\Username;
use Yume\Api\Domain\User\ValueObject\UserStatus;

/**
 * The user aggregate root.
 *
 * It knows nothing about PDO, HTTP or PHP sessions; persistence is the
 * repository's problem. Credentials deliberately live in Domain\Auth so that a
 * profile read never pulls a password hash into memory.
 */
final class User
{
    /** @param list<string> $roles role slugs */
    private function __construct(
        public readonly UserId $id,
        private Username $username,
        private Email $email,
        private UserStatus $status,
        private ?\DateTimeImmutable $emailVerifiedAt,
        private array $roles,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    /** @param list<string> $roles */
    public static function register(
        UserId $id,
        Username $username,
        Email $email,
        \DateTimeImmutable $now,
        array $roles = ['user'],
        bool $requiresEmailVerification = true,
    ): self {
        return new self(
            $id,
            $username,
            $email,
            $requiresEmailVerification ? UserStatus::PendingVerification : UserStatus::Active,
            $requiresEmailVerification ? null : $now,
            $roles,
            $now,
            $now,
        );
    }

    /** @param list<string> $roles */
    public static function reconstitute(
        UserId $id,
        Username $username,
        Email $email,
        UserStatus $status,
        ?\DateTimeImmutable $emailVerifiedAt,
        array $roles,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $username, $email, $status, $emailVerifiedAt, $roles, $createdAt, $updatedAt);
    }

    public function username(): Username
    {
        return $this->username;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function emailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return list<string> */
    public function roles(): array
    {
        return $this->roles;
    }

    public function hasRole(string $slug): bool
    {
        return in_array($slug, $this->roles, true);
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt instanceof \DateTimeImmutable;
    }

    public function verifyEmail(\DateTimeImmutable $now): void
    {
        if ($this->isEmailVerified()) {
            return;
        }

        $this->emailVerifiedAt = $now;

        if ($this->status === UserStatus::PendingVerification) {
            $this->status = UserStatus::Active;
        }

        $this->updatedAt = $now;
    }

    public function suspend(\DateTimeImmutable $now): void
    {
        $this->status = UserStatus::Suspended;
        $this->updatedAt = $now;
    }

    public function reinstate(\DateTimeImmutable $now): void
    {
        $this->status = $this->isEmailVerified() ? UserStatus::Active : UserStatus::PendingVerification;
        $this->updatedAt = $now;
    }

    public function changeEmail(Email $email, \DateTimeImmutable $now): void
    {
        if ($email->value === $this->email->value) {
            return;
        }

        $this->email = $email;
        $this->emailVerifiedAt = null;
        $this->status = UserStatus::PendingVerification;
        $this->updatedAt = $now;
    }

    public function canAuthenticate(): bool
    {
        return $this->status->canAuthenticate();
    }
}
