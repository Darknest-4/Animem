<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Episode\Repository;

use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Episode\Entity\Episode;
use Yume\Api\Domain\Episode\Entity\EpisodeRelease;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;

interface EpisodeRepositoryInterface
{
    public function findById(EpisodeId $id): ?Episode;

    public function findByNumber(AnimeId $animeId, int $number): ?Episode;

    /** @return list<Episode> ordered by episode number */
    public function forAnime(AnimeId $animeId, bool $includeUnpublished = false): array;

    public function save(Episode $episode): void;

    public function delete(EpisodeId $id): void;

    /** @return list<EpisodeRelease> */
    public function releasesFor(EpisodeId $episodeId): array;

    public function countReleases(EpisodeId $episodeId): int;

    public function saveRelease(EpisodeRelease $release): void;

    public function deleteRelease(string $releaseId): void;

    public function incrementViews(EpisodeId $episodeId): void;
}
