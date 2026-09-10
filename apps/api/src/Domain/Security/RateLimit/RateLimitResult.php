<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\RateLimit;

final readonly class RateLimitResult
{
    public function __construct(
        public bool $allowed,
        public int $remaining,
        public int $limit,
        public int $retryAfterSeconds,
        public \DateTimeImmutable $resetsAt,
    ) {
    }

    /** @return array<string, string> RFC 9331 style headers */
    public function toHeaders(): array
    {
        $headers = [
            'RateLimit-Limit' => (string) $this->limit,
            'RateLimit-Remaining' => (string) max(0, $this->remaining),
            'RateLimit-Reset' => (string) $this->resetsAt->getTimestamp(),
        ];

        if (!$this->allowed) {
            $headers['Retry-After'] = (string) $this->retryAfterSeconds;
        }

        return $headers;
    }
}
