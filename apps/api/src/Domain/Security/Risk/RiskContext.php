<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Risk;

use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;

/** Everything the risk engine is allowed to see about one request. */
final readonly class RiskContext
{
    /** @param array<string, string> $headers lower-cased header names */
    public function __construct(
        public IpAddress $ip,
        public UserAgent $userAgent,
        public string $method,
        public string $path,
        public array $headers = [],
        public ?UserId $userId = null,
        public bool $isAuthenticated = false,
        public int $recentFailedLogins = 0,
        public int $requestsInWindow = 0,
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isSensitivePath(): bool
    {
        foreach (['/auth/login', '/auth/register', '/auth/password', '/admin'] as $prefix) {
            if (str_contains($this->path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function withCounters(int $recentFailedLogins, int $requestsInWindow): self
    {
        return new self(
            $this->ip,
            $this->userAgent,
            $this->method,
            $this->path,
            $this->headers,
            $this->userId,
            $this->isAuthenticated,
            $recentFailedLogins,
            $requestsInWindow,
        );
    }
}
