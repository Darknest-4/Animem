<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Handler;

use Yume\Api\Application\Anime\DTO\AnimeView;
use Yume\Api\Application\Anime\Query\GetAnimeQuery;
use Yume\Api\Domain\Anime\Exception\AnimeNotFoundException;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;

final class GetAnimeHandler
{
    public function __construct(
        private readonly AnimeRepositoryInterface $anime,
        private readonly UploaderRepositoryInterface $uploaders,
    ) {
    }

    public function __invoke(GetAnimeQuery $query): AnimeView
    {
        $anime = $this->resolve($query->identifier);

        // A draft answers 404 rather than 403 for a caller without edit rights:
        // an unpublished entry should not be discoverable at all.
        if ($anime === null || (!$anime->isPublished() && !$query->includeDrafts)) {
            throw AnimeNotFoundException::withId($query->identifier);
        }

        return AnimeView::fromEntity($anime, $this->uploaders->forAnime($anime->id));
    }

    private function resolve(string $identifier): ?\Yume\Api\Domain\Anime\Entity\Anime
    {
        try {
            return $this->anime->findById(AnimeId::fromString($identifier));
        } catch (\InvalidArgumentException) {
            // Not a UUID, so treat it as a slug.
        }

        try {
            return $this->anime->findBySlug(Slug::fromString($identifier));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
