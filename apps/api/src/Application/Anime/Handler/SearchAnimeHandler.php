<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Handler;

use Yume\Api\Application\Anime\DTO\AnimeView;
use Yume\Api\Application\Anime\Query\SearchAnimeQuery;
use Yume\Api\Domain\Anime\Entity\Anime;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Anime\Repository\AnimeSearchCriteria;
use Yume\Api\Domain\Anime\ValueObject\AiringStatus;
use Yume\Api\Domain\Anime\ValueObject\MediaType;
use Yume\Api\Domain\Anime\ValueObject\Season;

final class SearchAnimeHandler
{
    public function __construct(private readonly AnimeRepositoryInterface $anime)
    {
    }

    /** @return array{items: list<array<string, mixed>>, pagination: array<string, int>} */
    public function __invoke(SearchAnimeQuery $query): array
    {
        $criteria = new AnimeSearchCriteria(
            $query->query,
            $query->genre,
            $query->mediaType !== null ? MediaType::tryFrom($query->mediaType) : null,
            $query->status !== null ? AiringStatus::tryFrom($query->status) : null,
            $query->season !== null ? Season::tryFrom($query->season) : null,
            $query->seasonYear,
            $query->includeDrafts,
            in_array($query->sort, AnimeSearchCriteria::SORTS, true) ? $query->sort : 'recent',
        );

        $criteria = $criteria->withPage($query->page, $query->perPage);

        $total = $this->anime->count($criteria);
        $perPage = $criteria->limit;

        return [
            'items' => array_map(
                static fn (Anime $anime): array => AnimeView::summaryFromEntity($anime),
                $this->anime->search($criteria),
            ),
            'pagination' => [
                'page' => max(1, $query->page),
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
            ],
        ];
    }
}
