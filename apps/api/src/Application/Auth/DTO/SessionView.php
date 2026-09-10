<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\DTO;

use Yume\Api\Domain\Auth\Entity\Session;

final readonly class SessionView
{
    public function __construct(
        public string $id,
        public string $createdIp,
        public string $createdUserAgent,
        public string $createdAt,
        public string $lastSeenAt,
        public string $expiresAt,
        public bool $isCurrent,
    ) {
    }

    public static function fromEntity(Session $session, ?string $currentSessionId): self
    {
        return new self(
            $session->id->value,
            $session->createdIp->value,
            $session->createdUserAgent->value,
            $session->createdAt->format(\DateTimeInterface::ATOM),
            $session->lastSeenAt()->format(\DateTimeInterface::ATOM),
            $session->expiresAt()->format(\DateTimeInterface::ATOM),
            $currentSessionId !== null && $session->id->value === $currentSessionId,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_ip' => $this->createdIp,
            'created_user_agent' => $this->createdUserAgent,
            'created_at' => $this->createdAt,
            'last_seen_at' => $this->lastSeenAt,
            'expires_at' => $this->expiresAt,
            'is_current' => $this->isCurrent,
        ];
    }
}
