<?php

declare(strict_types=1);

namespace Yume\Api\Application\Feature\Query;

use Yume\Contracts\Bus\QueryInterface;

final readonly class ListFeatureFlagsQuery implements QueryInterface
{
    /** @param list<string> $roles */
    public function __construct(
        public ?string $userId = null,
        public array $roles = [],
        public ?string $ip = null,
        /** Admins get strategy and rollout detail; everyone else gets on/off. */
        public bool $includeDefinition = false,
    ) {
    }
}
