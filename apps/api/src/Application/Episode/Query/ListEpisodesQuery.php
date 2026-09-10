<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Query;

use Yume\Contracts\Bus\QueryInterface;

final readonly class ListEpisodesQuery implements QueryInterface
{
    public function __construct(
        public string $animeId,
        public bool $includeDrafts = false,
    ) {
    }
}
