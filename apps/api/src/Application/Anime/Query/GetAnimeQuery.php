<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Query;

use Yume\Contracts\Bus\QueryInterface;

final readonly class GetAnimeQuery implements QueryInterface
{
    public function __construct(
        /** A UUID or a slug; the handler accepts either. */
        public string $identifier,
        public bool $includeDrafts = false,
    ) {
    }
}
