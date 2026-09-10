<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\Repository;

use Yume\Api\Domain\Anime\ValueObject\AiringStatus;
use Yume\Api\Domain\Anime\ValueObject\MediaType;
use Yume\Api\Domain\Anime\ValueObject\Season;

/**
 * A typed query object instead of a dozen nullable repository arguments.
 *
 * `includeUnpublished` defaults to false so a caller that forgets about drafts
 * leaks nothing — the safe value is the one you get by not thinking about it.
 */
final readonly class AnimeSearchCriteria
{
    public const SORTS = ['recent', 'title', 'score', 'season'];

    public function __construct(
        public ?string $query = null,
        public ?string $genreSlug = null,
        public ?MediaType $mediaType = null,
        public ?AiringStatus $status = null,
        public ?Season $season = null,
        public ?int $seasonYear = null,
        public bool $includeUnpublished = false,
        public string $sort = 'recent',
        public int $limit = 24,
        public int $offset = 0,
    ) {
        if (!in_array($sort, self::SORTS, true)) {
            throw new \InvalidArgumentException('Unknown sort: ' . $sort);
        }
    }

    public function withPage(int $page, int $perPage): self
    {
        return new self(
            $this->query,
            $this->genreSlug,
            $this->mediaType,
            $this->status,
            $this->season,
            $this->seasonYear,
            $this->includeUnpublished,
            $this->sort,
            max(1, min(100, $perPage)),
            max(0, ($page - 1) * $perPage),
        );
    }

    public function includingDrafts(): self
    {
        return new self(
            $this->query,
            $this->genreSlug,
            $this->mediaType,
            $this->status,
            $this->season,
            $this->seasonYear,
            true,
            $this->sort,
            $this->limit,
            $this->offset,
        );
    }
}
