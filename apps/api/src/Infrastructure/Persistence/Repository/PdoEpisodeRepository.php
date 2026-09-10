<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Community\ValueObject\UploaderId;
use Yume\Api\Domain\Episode\Entity\Episode;
use Yume\Api\Domain\Episode\Entity\EpisodeRelease;
use Yume\Api\Domain\Episode\Repository\EpisodeRepositoryInterface;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;
use Yume\Api\Domain\Episode\ValueObject\ReleaseKind;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Persistence\ConnectionInterface;

final class PdoEpisodeRepository implements EpisodeRepositoryInterface
{
    private const COLUMNS = <<<'SQL'
        SELECT id, anime_id, number, title, title_japanese, synopsis,
               aired_on, duration_sec, is_published, created_at, updated_at, created_by
        FROM episodes
        SQL;

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function findById(EpisodeId $id): ?Episode
    {
        $row = $this->connection->selectOne(self::COLUMNS . ' WHERE id = :id', ['id' => $id->value]);

        return $row === null ? null : self::hydrate($row);
    }

    public function findByNumber(AnimeId $animeId, int $number): ?Episode
    {
        $row = $this->connection->selectOne(
            self::COLUMNS . ' WHERE anime_id = :anime_id AND number = :number',
            ['anime_id' => $animeId->value, 'number' => $number],
        );

        return $row === null ? null : self::hydrate($row);
    }

    public function forAnime(AnimeId $animeId, bool $includeUnpublished = false): array
    {
        // Two complete statements rather than a conditional fragment: there are
        // only two shapes, and both stay readable and greppable this way.
        $sql = $includeUnpublished
            ? self::COLUMNS . ' WHERE anime_id = :anime_id ORDER BY number ASC'
            : self::COLUMNS . ' WHERE anime_id = :anime_id AND is_published = TRUE ORDER BY number ASC';

        return array_map(self::hydrate(...), $this->connection->select($sql, ['anime_id' => $animeId->value]));
    }

    public function save(Episode $episode): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO episodes (id, anime_id, number, title, title_japanese, synopsis,
                                  aired_on, duration_sec, is_published, created_at, updated_at, created_by)
            VALUES (:id, :anime_id, :number, :title, :title_japanese, :synopsis,
                    :aired_on, :duration_sec, :is_published, :created_at, :updated_at, :created_by)
            ON CONFLICT (id) DO UPDATE SET
                number         = EXCLUDED.number,
                title          = EXCLUDED.title,
                title_japanese = EXCLUDED.title_japanese,
                synopsis       = EXCLUDED.synopsis,
                aired_on       = EXCLUDED.aired_on,
                duration_sec   = EXCLUDED.duration_sec,
                is_published   = EXCLUDED.is_published,
                updated_at     = EXCLUDED.updated_at
            SQL,
            [
                'id' => $episode->id->value,
                'anime_id' => $episode->animeId->value,
                'number' => $episode->number(),
                'title' => $episode->title(),
                'title_japanese' => $episode->titleJapanese(),
                'synopsis' => $episode->synopsis(),
                'aired_on' => $episode->airedOn()?->format('Y-m-d'),
                'duration_sec' => $episode->durationSeconds(),
                'is_published' => $episode->isPublished(),
                'created_at' => $episode->createdAt->format('Y-m-d H:i:sP'),
                'updated_at' => $episode->updatedAt()->format('Y-m-d H:i:sP'),
                'created_by' => $episode->createdBy?->value,
            ],
        );
    }

    public function delete(EpisodeId $id): void
    {
        $this->connection->execute('DELETE FROM episodes WHERE id = :id', ['id' => $id->value]);
    }

    public function releasesFor(EpisodeId $episodeId): array
    {
        $rows = $this->connection->select(
            <<<'SQL'
            SELECT id, episode_id, uploader_id, language, kind, host, url, created_at, created_by
            FROM episode_releases
            WHERE episode_id = :episode_id
            ORDER BY created_at ASC
            SQL,
            ['episode_id' => $episodeId->value],
        );

        return array_map(
            static fn (array $row): EpisodeRelease => EpisodeRelease::reconstitute(
                (string) $row['id'],
                EpisodeId::fromString((string) $row['episode_id']),
                UploaderId::fromString((string) $row['uploader_id']),
                (string) $row['language'],
                ReleaseKind::from((string) $row['kind']),
                (string) $row['host'],
                (string) $row['url'],
                new \DateTimeImmutable((string) $row['created_at']),
                is_string($row['created_by'] ?? null) ? UserId::fromString((string) $row['created_by']) : null,
            ),
            $rows,
        );
    }

    public function countReleases(EpisodeId $episodeId): int
    {
        return (int) $this->connection->scalar(
            'SELECT count(*) FROM episode_releases WHERE episode_id = :episode_id',
            ['episode_id' => $episodeId->value],
        );
    }

    public function saveRelease(EpisodeRelease $release): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO episode_releases (id, episode_id, uploader_id, language, kind, host, url, created_at, created_by)
            VALUES (:id, :episode_id, :uploader_id, :language, :kind, :host, :url, :created_at, :created_by)
            ON CONFLICT (episode_id, uploader_id, language, kind)
            DO UPDATE SET url = EXCLUDED.url, host = EXCLUDED.host, created_at = EXCLUDED.created_at
            SQL,
            [
                'id' => $release->id,
                'episode_id' => $release->episodeId->value,
                'uploader_id' => $release->uploaderId->value,
                'language' => $release->language,
                'kind' => $release->kind->value,
                'host' => $release->host,
                'url' => $release->url,
                'created_at' => $release->createdAt->format('Y-m-d H:i:sP'),
                'created_by' => $release->createdBy?->value,
            ],
        );
    }

    public function deleteRelease(string $releaseId): void
    {
        $this->connection->execute('DELETE FROM episode_releases WHERE id = :id', ['id' => $releaseId]);
    }

    public function incrementViews(EpisodeId $episodeId): void
    {
        // Atomic upsert: two concurrent viewers cannot both read the same count
        // and write the same increment.
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO episode_view_counts (episode_id, views, updated_at)
            VALUES (:episode_id, 1, now())
            ON CONFLICT (episode_id)
            DO UPDATE SET views = episode_view_counts.views + 1, updated_at = now()
            SQL,
            ['episode_id' => $episodeId->value],
        );
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): Episode
    {
        return Episode::reconstitute(
            EpisodeId::fromString((string) $row['id']),
            AnimeId::fromString((string) $row['anime_id']),
            (int) $row['number'],
            is_string($row['title'] ?? null) ? (string) $row['title'] : null,
            is_string($row['title_japanese'] ?? null) ? (string) $row['title_japanese'] : null,
            is_string($row['synopsis'] ?? null) ? (string) $row['synopsis'] : null,
            is_string($row['aired_on'] ?? null) ? new \DateTimeImmutable((string) $row['aired_on']) : null,
            isset($row['duration_sec']) ? (int) $row['duration_sec'] : null,
            (bool) $row['is_published'],
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['updated_at']),
            is_string($row['created_by'] ?? null) ? UserId::fromString((string) $row['created_by']) : null,
        );
    }
}
