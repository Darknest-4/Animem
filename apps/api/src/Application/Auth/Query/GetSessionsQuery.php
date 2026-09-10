<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Query;

use Yume\Contracts\Bus\QueryInterface;

final readonly class GetSessionsQuery implements QueryInterface
{
    public function __construct(
        public string $userId,
        /** Marks which entry is the caller's own session. */
        public ?string $currentSessionId = null,
    ) {
    }
}
