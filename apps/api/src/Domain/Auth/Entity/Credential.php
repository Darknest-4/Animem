<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Entity;

use Yume\Api\Domain\Auth\ValueObject\PasswordAlgorithm;
use Yume\Api\Domain\Auth\ValueObject\PasswordHash;
use Yume\Api\Domain\User\ValueObject\UserId;

/**
 * A user's password credential, separate from the user aggregate so that
 * reading a profile never loads a hash.
 */
final class Credential
{
    private function __construct(
        public readonly UserId $userId,
        private PasswordHash $passwordHash,
        private ?\DateTimeImmutable $passwordChangedAt,
        private int $failedAttempts,
        private ?\DateTimeImmutable $lockedUntil,
    ) {
    }

    public static function create(UserId $userId, PasswordHash $hash, \DateTimeImmutable $now): self
    {
        return new self($userId, $hash, $now, 0, null);
    }

    public static function reconstitute(
        UserId $userId,
        PasswordHash $hash,
        ?\DateTimeImmutable $passwordChangedAt,
        int $failedAttempts,
        ?\DateTimeImmutable $lockedUntil,
    ): self {
        return new self($userId, $hash, $passwordChangedAt, $failedAttempts, $lockedUntil);
    }

    public function passwordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function failedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function lockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function passwordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    public function isLocked(\DateTimeImmutable $now): bool
    {
        return $this->lockedUntil instanceof \DateTimeImmutable && $this->lockedUntil > $now;
    }

    /** Called after a verified password, including when only the algorithm changed. */
    public function replacePassword(PasswordHash $hash, \DateTimeImmutable $now): void
    {
        $this->passwordHash = $hash;
        $this->passwordChangedAt = $now;
        $this->failedAttempts = 0;
        $this->lockedUntil = null;
    }

    /** Silent upgrade from the legacy SHA-256 hash; not a user-initiated change. */
    public function upgradeHash(PasswordHash $hash): void
    {
        if ($hash->algorithm === PasswordAlgorithm::LegacySha256) {
            throw new \InvalidArgumentException('Refusing to downgrade a credential to a legacy hash.');
        }

        $this->passwordHash = $hash;
    }

    public function recordFailure(\DateTimeImmutable $now, int $maxAttempts, int $lockSeconds): void
    {
        ++$this->failedAttempts;

        if ($this->failedAttempts >= $maxAttempts) {
            $this->lockedUntil = $now->modify(sprintf('+%d seconds', $lockSeconds));
            $this->failedAttempts = 0;
        }
    }

    public function recordSuccess(): void
    {
        $this->failedAttempts = 0;
        $this->lockedUntil = null;
    }
}
