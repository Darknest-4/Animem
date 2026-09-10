<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\Repository;

use Yume\Api\Domain\Anime\Entity\Anime;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\Slug;

interface AnimeRepositoryInterface
{
    public function findById(AnimeId $id): ?Anime;

    public function findBySlug(Slug $slug): ?Anime;

    public function findByMalId(int $malId): ?Anime;

    /**
     * @param AnimeSearchCriteria $criteria
     * @return list<Anime>
     */
    public function search(AnimeSearchCriteria $criteria): array;

    public function count(AnimeSearchCriteria $criteria): int;

    public function save(Anime $anime): void;

    public function delete(AnimeId $id): void;

    /** Ensures the slug is free, appending a discriminator if it is not. */
    public function reserveSlug(Slug $candidate, ?AnimeId $excluding = null): Slug;
}
