<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Risk;

enum RiskAction: string
{
    case Allow = 'allow';
    case Monitor = 'monitor';
    case RateLimit = 'rate_limit';
    case Challenge = 'challenge';
    case Restrict = 'restrict';
    case Block = 'block';

    public function stopsRequest(): bool
    {
        return $this === self::Block || $this === self::Challenge;
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::Block => 403,
            self::Challenge => 428,
            self::RateLimit => 429,
            default => 200,
        };
    }
}
