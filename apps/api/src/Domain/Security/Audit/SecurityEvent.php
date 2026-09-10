<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Audit;

use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;

/**
 * An append-only audit record.
 *
 * Every authorization decision, ban and authentication outcome lands here. The
 * legacy site had no audit trail at all, so a compromise would have been
 * undetectable and unreconstructable.
 */
final readonly class SecurityEvent
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $id,
        public SecurityEventType $type,
        public ?UserId $userId,
        public IpAddress $ip,
        public UserAgent $userAgent,
        public string $method,
        public string $path,
        public int $riskScore,
        public array $metadata,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function severity(): string
    {
        return $this->type->severity();
    }
}
