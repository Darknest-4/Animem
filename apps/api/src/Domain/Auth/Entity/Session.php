<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Entity;

use Yume\Api\Domain\Auth\ValueObject\SessionId;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;

/**
 * A server-side authenticated session.
 *
 * The client holds an opaque token; the server holds its digest plus the
 * metadata needed to expire, rotate and revoke it. This is the direct
 * replacement for the legacy `userID` cookie, which was an unsigned, 1-year,
 * client-editable integer that granted whatever account it named.
 */
final class Session
{
    private function __construct(
        public readonly SessionId $id,
        public readonly UserId $userId,
        private TokenHash $tokenHash,
        public readonly IpAddress $createdIp,
        public readonly UserAgent $createdUserAgent,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $lastSeenAt,
        private \DateTimeImmutable $expiresAt,
        private ?\DateTimeImmutable $revokedAt,
        private ?string $revokedReason,
    ) {
    }

    public static function start(
        SessionId $id,
        UserId $userId,
        TokenHash $tokenHash,
        IpAddress $ip,
        UserAgent $userAgent,
        \DateTimeImmutable $now,
        int $lifetimeSeconds,
    ): self {
        return new self(
            $id,
            $userId,
            $tokenHash,
            $ip,
            $userAgent,
            $now,
            $now,
            $now->modify(sprintf('+%d seconds', $lifetimeSeconds)),
            null,
            null,
        );
    }

    public static function reconstitute(
        SessionId $id,
        UserId $userId,
        TokenHash $tokenHash,
        IpAddress $createdIp,
        UserAgent $createdUserAgent,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $lastSeenAt,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $revokedAt,
        ?string $revokedReason,
    ): self {
        return new self(
            $id,
            $userId,
            $tokenHash,
            $createdIp,
            $createdUserAgent,
            $createdAt,
            $lastSeenAt,
            $expiresAt,
            $revokedAt,
            $revokedReason,
        );
    }

    public function tokenHash(): TokenHash
    {
        return $this->tokenHash;
    }

    public function lastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function revokedReason(): ?string
    {
        return $this->revokedReason;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt instanceof \DateTimeImmutable;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function isActive(\DateTimeImmutable $now): bool
    {
        return !$this->isRevoked() && !$this->isExpired($now);
    }

    public function touch(\DateTimeImmutable $now, int $idleExtensionSeconds): void
    {
        $this->lastSeenAt = $now;

        // Sliding expiry, capped by the absolute lifetime the caller already set.
        $extended = $now->modify(sprintf('+%d seconds', $idleExtensionSeconds));
        if ($extended > $this->expiresAt) {
            $this->expiresAt = $extended;
        }
    }

    /**
     * Session fixation defence: the token digest is replaced while the session row
     * (and therefore its audit trail) survives.
     */
    public function rotateToken(TokenHash $newHash): void
    {
        $this->tokenHash = $newHash;
    }

    public function revoke(\DateTimeImmutable $now, string $reason): void
    {
        if ($this->isRevoked()) {
            return;
        }

        $this->revokedAt = $now;
        $this->revokedReason = $reason;
    }
}
