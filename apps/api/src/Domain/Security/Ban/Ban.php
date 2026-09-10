<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Ban;

final readonly class Ban
{
    public function __construct(
        public string $id,
        public BanScope $scope,
        public BanType $type,
        /** IP, subnet key, or user id depending on scope; null for a global ban. */
        public ?string $subject,
        public string $reason,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $expiresAt,
        public ?string $createdBy = null,
    ) {
    }

    public function isActive(\DateTimeImmutable $now): bool
    {
        return $this->expiresAt === null || $this->expiresAt > $now;
    }

    public function isPermanent(): bool
    {
        return $this->expiresAt === null;
    }
}
