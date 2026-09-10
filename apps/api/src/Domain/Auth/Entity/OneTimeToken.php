<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Entity;

use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Auth\ValueObject\TokenPurpose;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\User\ValueObject\UserId;

/**
 * A single-use, expiring token for email verification or password reset.
 *
 * Stored as a SHA-256 digest, exactly like a session token: a database leak
 * must not hand an attacker a working password-reset link. Consumption is
 * recorded rather than the row being deleted, so a replayed link is
 * distinguishable from an expired one in the audit trail.
 */
final class OneTimeToken
{
    private function __construct(
        public readonly string $id,
        public readonly UserId $userId,
        public readonly TokenPurpose $purpose,
        public readonly TokenHash $tokenHash,
        public readonly ?IpAddress $requestedIp,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $expiresAt,
        private ?\DateTimeImmutable $consumedAt,
    ) {
    }

    public static function issue(
        string $id,
        UserId $userId,
        TokenPurpose $purpose,
        TokenHash $tokenHash,
        \DateTimeImmutable $now,
        ?IpAddress $requestedIp = null,
    ): self {
        return new self(
            $id,
            $userId,
            $purpose,
            $tokenHash,
            $requestedIp,
            $now,
            $now->modify(sprintf('+%d seconds', $purpose->lifetimeSeconds())),
            null,
        );
    }

    public static function reconstitute(
        string $id,
        UserId $userId,
        TokenPurpose $purpose,
        TokenHash $tokenHash,
        ?IpAddress $requestedIp,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $consumedAt,
    ): self {
        return new self($id, $userId, $purpose, $tokenHash, $requestedIp, $createdAt, $expiresAt, $consumedAt);
    }

    public function consumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function isConsumed(): bool
    {
        return $this->consumedAt instanceof \DateTimeImmutable;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function isUsable(\DateTimeImmutable $now): bool
    {
        return !$this->isConsumed() && !$this->isExpired($now);
    }

    public function consume(\DateTimeImmutable $now): void
    {
        if ($this->isConsumed()) {
            throw new \LogicException('This token has already been used.');
        }

        $this->consumedAt = $now;
    }
}
