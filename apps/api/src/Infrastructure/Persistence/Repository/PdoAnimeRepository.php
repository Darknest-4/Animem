<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Anime\Entity\Anime;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Anime\Repository\AnimeSearchCriteria;
use Yume\Api\Domain\Anime\ValueObject\AiringStatus;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\MediaType;
use Yume\Api\Domain\Anime\ValueObject\Season;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Infrastructure\Persistence\PostgreSQL\PgArray;
use Yume\Contracts\Persistence\ConnectionInterface;

/**
 * Catalogue reads and writes.
 *
 * The search query is assembled from a fixed vocabulary of literal fragments —
 * never from caller input — and every value travels as a bound parameter. The
 * only thing the request decides is *which* constant fragments are switched on.
 */
final class PdoAnimeRepository implements AnimeRepositoryInterface
{
    private const COLUMNS = <<<'SQL'
        SELECT a.id, a.mal_id, a.slug, a.title, a.title_english, a.title_japanese, a.synopsis,
               a.media_type, a.status, a.source, a.age_rating, a.episode_count,
               a.season, a.season_year, a.aired_from, a.aired_to, a.score, a.cover_url,
               a.is_published, a.created_at, a.updated_at, a.created_by,
               COALESCE((SELECT array_agg(g.slug ORDER BY g.slug)
                         FROM anime_genres ag JOIN genres g ON g.id = ag.genre_id
                         WHERE ag.anime_id = a.id), ARRAY[]::varchar[]) AS genre_slugs,
               COALESCE((SELECT array_agg(s.slug ORDER BY s.slug)
                         FROM anime_studios asx JOIN studios s ON s.id = asx.studio_id
                         WHERE asx.anime_id = a.id), ARRAY[]::varchar[]) AS studio_slugs
        FROM anime a
        SQL;

    private const COUNT = 'SELECT count(*) FROM anime a';

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function findById(AnimeId $id): ?Anime
    {
        $row = $this->connection->selectOne(self::COLUMNS . ' WHERE a.id = :id', ['id' => $id->value]);

        return $row === null ? null : self::hydrate($row);
    }

    public function findBySlug(Slug $slug): ?Anime
    {
        $row = $this->connection->selectOne(self::COLUMNS . ' WHERE a.slug = :slug', ['slug' => $slug->value]);

        return $row === null ? null : self::hydrate($row);
    }

    public function findByMalId(int $malId): ?Anime
    {
        $row = $this->connection->selectOne(self::COLUMNS . ' WHERE a.mal_id = :mal_id', ['mal_id' => $malId]);

        return $row === null ? null : self::hydrate($row);
    }

    public function search(AnimeSearchCriteria $criteria): array
    {
        [$conditions, $params] = self::conditions($criteria);

        $sql = self::COLUMNS
            . $conditions
            . self::orderBy($criteria->sort)
            . ' LIMIT :limit OFFSET :offset';

        $rows = $this->connection->select($sql, [
            ...$params,
            'limit' => $criteria->limit,
            'offset' => $criteria->offset,
        ]);

        return array_map(self::hydrate(...), $rows);
    }

    public function count(AnimeSearchCriteria $criteria): int
    {
        [$conditions, $params] = self::conditions($criteria);

        return (int) $this->connection->scalar(self::COUNT . $conditions, $params);
    }

    public function save(Anime $anime): void
    {
        $this->connection->transaction(function () use ($anime): void {
            $this->connection->execute(
                <<<'SQL'
                INSERT INTO anime (id, mal_id, slug, title, title_english, title_japanese, synopsis,
                                   media_type, status, source, age_rating, episode_count,
                                   season, season_year, aired_from, aired_to, score, cover_url,
                                   is_published, created_at, updated_at, created_by)
                VALUES (:id, :mal_id, :slug, :title, :title_english, :title_japanese, :synopsis,
                        :media_type, :status, :source, :age_rating, :episode_count,
                        :season, :season_year, :aired_from, :aired_to, :score, :cover_url,
                        :is_published, :created_at, :updated_at, :created_by)
                ON CONFLICT (id) DO UPDATE SET
                    mal_id         = EXCLUDED.mal_id,
                    slug           = EXCLUDED.slug,
                    title          = EXCLUDED.title,
                    title_english  = EXCLUDED.title_english,
                    title_japanese = EXCLUDED.title_japanese,
                    synopsis       = EXCLUDED.synopsis,
                    media_type     = EXCLUDED.media_type,
                    status         = EXCLUDED.status,
                    source         = EXCLUDED.source,
                    age_rating     = EXCLUDED.age_rating,
                    episode_count  = EXCLUDED.episode_count,
                    season         = EXCLUDED.season,
                    season_year    = EXCLUDED.season_year,
                    aired_from     = EXCLUDED.aired_from,
                    aired_to       = EXCLUDED.aired_to,
                    score          = EXCLUDED.score,
                    cover_url      = EXCLUDED.cover_url,
                    is_published   = EXCLUDED.is_published,
                    updated_at     = EXCLUDED.updated_at
                SQL,
                [
                    'id' => $anime->id->value,
                    'mal_id' => $anime->malId(),
                    'slug' => $anime->slug()->value,
                    'title' => $anime->title(),
                    'title_english' => $anime->titleEnglish(),
                    'title_japanese' => $anime->titleJapanese(),
                    'synopsis' => $anime->synopsis(),
                    'media_type' => $anime->mediaType()->value,
                    'status' => $anime->status()->value,
                    'source' => $anime->source(),
                    'age_rating' => $anime->ageRating(),
                    'episode_count' => $anime->episodeCount(),
                    'season' => $anime->season()?->value,
                    'season_year' => $anime->seasonYear(),
                    'aired_from' => $anime->airedFrom()?->format('Y-m-d'),
                    'aired_to' => $anime->airedTo()?->format('Y-m-d'),
                    'score' => $anime->score(),
                    'cover_url' => $anime->coverUrl(),
                    'is_published' => $anime->isPublished(),
                    'created_at' => $anime->createdAt->format('Y-m-d H:i:sP'),
                    'updated_at' => $anime->updatedAt()->format('Y-m-d H:i:sP'),
                    'created_by' => $anime->createdBy?->value,
                ],
            );

            $this->syncTaxonomy($anime);
        });
    }

    public function delete(AnimeId $id): void
    {
        // anime_genres, anime_studios, anime_uploaders and episodes all cascade.
        $this->connection->execute('DELETE FROM anime WHERE id = :id', ['id' => $id->value]);
    }

    public function reserveSlug(Slug $candidate, ?AnimeId $excluding = null): Slug
    {
        $slug = $candidate;

        for ($suffix = 2; $suffix < 1000; ++$suffix) {
            $taken = (bool) $this->connection->scalar(
                'SELECT EXISTS (SELECT 1 FROM anime WHERE slug = :slug AND (:excluding::uuid IS NULL OR id <> :excluding::uuid))',
                ['slug' => $slug->value, 'excluding' => $excluding?->value],
            );

            if (!$taken) {
                return $slug;
            }

            $slug = $candidate->withSuffix($suffix);
        }

        throw new \RuntimeException('Unable to find a free slug; the base slug is implausibly contested.');
    }

    /**
     * Rewrites the genre and studio links, creating rows for slugs that are new.
     *
     * Delete-then-insert rather than a diff: the sets are a handful of rows and
     * the simpler code has no partial-update failure mode.
     */
    private function syncTaxonomy(Anime $anime): void
    {
        $this->connection->execute('DELETE FROM anime_genres WHERE anime_id = :id', ['id' => $anime->id->value]);
        $this->connection->execute('DELETE FROM anime_studios WHERE anime_id = :id', ['id' => $anime->id->value]);

        foreach ($anime->genreSlugs() as $genreSlug) {
            $this->connection->execute(
                'INSERT INTO genres (slug, name_en) VALUES (:slug, :name) ON CONFLICT (slug) DO NOTHING',
                ['slug' => $genreSlug, 'name' => ucwords(str_replace('-', ' ', $genreSlug))],
            );
            $this->connection->execute(
                <<<'SQL'
                INSERT INTO anime_genres (anime_id, genre_id)
                SELECT :anime_id, g.id FROM genres g WHERE g.slug = :slug
                ON CONFLICT DO NOTHING
                SQL,
                ['anime_id' => $anime->id->value, 'slug' => $genreSlug],
            );
        }

        foreach ($anime->studioSlugs() as $studioSlug) {
            $this->connection->execute(
                'INSERT INTO studios (slug, name) VALUES (:slug, :name) ON CONFLICT (slug) DO NOTHING',
                ['slug' => $studioSlug, 'name' => ucwords(str_replace('-', ' ', $studioSlug))],
            );
            $this->connection->execute(
                <<<'SQL'
                INSERT INTO anime_studios (anime_id, studio_id)
                SELECT :anime_id, s.id FROM studios s WHERE s.slug = :slug
                ON CONFLICT DO NOTHING
                SQL,
                ['anime_id' => $anime->id->value, 'slug' => $studioSlug],
            );
        }
    }

    /**
     * Builds the WHERE clause from constant fragments plus bound parameters.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function conditions(AnimeSearchCriteria $criteria): array
    {
        $fragments = [];
        $params = [];

        if (!$criteria->includeUnpublished) {
            $fragments[] = 'a.is_published = TRUE';
        }

        if ($criteria->query !== null && trim($criteria->query) !== '') {
            // Matches any of the three title fields; the trigram index on
            // a.title carries the common case.
            $fragments[] = '(a.title ILIKE :query OR a.title_english ILIKE :query OR a.title_japanese ILIKE :query)';
            $params['query'] = '%' . trim($criteria->query) . '%';
        }

        if ($criteria->genreSlug !== null) {
            $fragments[] = 'EXISTS (SELECT 1 FROM anime_genres ag JOIN genres g ON g.id = ag.genre_id'
                . ' WHERE ag.anime_id = a.id AND g.slug = :genre)';
            $params['genre'] = $criteria->genreSlug;
        }

        if ($criteria->mediaType instanceof MediaType) {
            $fragments[] = 'a.media_type = :media_type';
            $params['media_type'] = $criteria->mediaType->value;
        }

        if ($criteria->status instanceof AiringStatus) {
            $fragments[] = 'a.status = :status';
            $params['status'] = $criteria->status->value;
        }

        if ($criteria->season instanceof Season) {
            $fragments[] = 'a.season = :season';
            $params['season'] = $criteria->season->value;
        }

        if ($criteria->seasonYear !== null) {
            $fragments[] = 'a.season_year = :season_year';
            $params['season_year'] = $criteria->seasonYear;
        }

        $clause = $fragments === [] ? '' : ' WHERE ' . implode(' AND ', $fragments);

        return [$clause, $params];
    }

    /** Closed mapping; AnimeSearchCriteria has already rejected anything else. */
    private static function orderBy(string $sort): string
    {
        return match ($sort) {
            'title' => ' ORDER BY a.title ASC',
            'score' => ' ORDER BY a.score DESC NULLS LAST, a.title ASC',
            'season' => ' ORDER BY a.season_year DESC NULLS LAST, a.aired_from DESC NULLS LAST',
            default => ' ORDER BY a.created_at DESC',
        };
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): Anime
    {
        return Anime::reconstitute(
            AnimeId::fromString((string) $row['id']),
            Slug::fromString((string) $row['slug']),
            (string) $row['title'],
            self::stringOrNull($row['title_english'] ?? null),
            self::stringOrNull($row['title_japanese'] ?? null),
            self::stringOrNull($row['synopsis'] ?? null),
            MediaType::from((string) $row['media_type']),
            AiringStatus::from((string) $row['status']),
            isset($row['mal_id']) ? (int) $row['mal_id'] : null,
            self::stringOrNull($row['source'] ?? null),
            self::stringOrNull($row['age_rating'] ?? null),
            isset($row['episode_count']) ? (int) $row['episode_count'] : null,
            is_string($row['season'] ?? null) ? Season::from((string) $row['season']) : null,
            isset($row['season_year']) ? (int) $row['season_year'] : null,
            self::dateOrNull($row['aired_from'] ?? null),
            self::dateOrNull($row['aired_to'] ?? null),
            isset($row['score']) ? (float) $row['score'] : null,
            self::stringOrNull($row['cover_url'] ?? null),
            (bool) $row['is_published'],
            PgArray::toStrings($row['genre_slugs'] ?? null),
            PgArray::toStrings($row['studio_slugs'] ?? null),
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['updated_at']),
            is_string($row['created_by'] ?? null) ? UserId::fromString((string) $row['created_by']) : null,
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function dateOrNull(mixed $value): ?\DateTimeImmutable
    {
        return is_string($value) && $value !== '' ? new \DateTimeImmutable($value) : null;
    }
}
