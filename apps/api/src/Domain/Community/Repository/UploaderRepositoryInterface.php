<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Community\Repository;

use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\Community\Entity\Uploader;
use Yume\Api\Domain\Community\ValueObject\UploaderId;

interface UploaderRepositoryInterface
{
    public function findById(UploaderId $id): ?Uploader;

    public function findBySlug(Slug $slug): ?Uploader;

    /** @return list<Uploader> */
    public function all(bool $includeInactive = false): array;

    /** @return list<Uploader> groups that have claimed this title */
    public function forAnime(AnimeId $animeId): array;

    public function save(Uploader $uploader): void;

    /**
     * Replaces the set of groups working on a title.
     *
     * This is the normalised form of the legacy
     * `JSON_EXTRACT(save,'$.fansub') LIKE '%"12"%'` lookup.
     *
     * @param list<UploaderId> $uploaderIds
     */
    public function setAnimeUploaders(AnimeId $animeId, array $uploaderIds, \DateTimeImmutable $now): void;

    public function slugExists(Slug $slug, ?UploaderId $excluding = null): bool;
}
