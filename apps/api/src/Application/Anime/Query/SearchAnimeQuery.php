<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Query;

use Yume\Contracts\Bus\QueryInterface;

final readonly class SearchAnimeQuery implements QueryInterface
{
    public function __construct(
        public ?string $query = null,
        public ?string $genre = null,
        public ?string $mediaType = null,
        public ?string $status = null,
        public ?string $season = null,
        public ?int $seasonYear = null,
        public string $sort = 'recent',
        public int $page = 1,
        public int $perPage = 24,
        /** Only ever true for a caller holding anime.edit; the handler does not decide. */
        public bool $includeDrafts = false,
    ) {
    }
}
