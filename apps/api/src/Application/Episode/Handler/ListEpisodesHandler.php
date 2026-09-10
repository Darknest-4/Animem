<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Handler;

use Yume\Api\Application\Episode\DTO\EpisodeView;
use Yume\Api\Application\Episode\Query\ListEpisodesQuery;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Community\Entity\Uploader;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;
use Yume\Api\Domain\Episode\Entity\Episode;
use Yume\Api\Domain\Episode\Repository\EpisodeRepositoryInterface;

final class ListEpisodesHandler
{
    public function __construct(
        private readonly EpisodeRepositoryInterface $episodes,
        private readonly UploaderRepositoryInterface $uploaders,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function __invoke(ListEpisodesQuery $query): array
    {
        $animeId = AnimeId::fromString($query->animeId);
        $episodes = $this->episodes->forAnime($animeId, $query->includeDrafts);

        // Names are resolved once for the whole season rather than per release,
        // which is where the legacy episode list spent most of its query budget.
        $names = [];
        foreach ($this->uploaders->forAnime($animeId) as $uploader) {
            $names[$uploader->id->value] = $uploader->name();
        }

        return array_map(
            function (Episode $episode) use ($names): array {
                $releases = $this->episodes->releasesFor($episode->id);

                foreach ($releases as $release) {
                    if (!isset($names[$release->uploaderId->value])) {
                        // A group that released an episode without claiming the
                        // title still deserves a credit line.
                        $uploader = $this->uploaders->findById($release->uploaderId);
                        $names[$release->uploaderId->value] = $uploader instanceof Uploader
                            ? $uploader->name()
                            : 'Ismeretlen';
                    }
                }

                return EpisodeView::fromEntity($episode, $releases, $names)->toArray();
            },
            $episodes,
        );
    }
}
