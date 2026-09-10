<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\Community\Entity\Uploader;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;
use Yume\Api\Domain\Community\ValueObject\UploaderId;
use Yume\Contracts\Persistence\ConnectionInterface;

final class PdoUploaderRepository implements UploaderRepositoryInterface
{
    private const COLUMNS = <<<'SQL'
        SELECT id, slug, name, description, website_url, facebook_url, video_url,
               email, is_active, created_at, updated_at
        FROM uploaders
        SQL;

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function findById(UploaderId $id): ?Uploader
    {
        $row = $this->connection->selectOne(self::COLUMNS . ' WHERE id = :id', ['id' => $id->value]);

        return $row === null ? null : self::hydrate($row);
    }

    public function findBySlug(Slug $slug): ?Uploader
    {
        $row = $this->connection->selectOne(self::COLUMNS . ' WHERE slug = :slug', ['slug' => $slug->value]);

        return $row === null ? null : self::hydrate($row);
    }

    public function all(bool $includeInactive = false): array
    {
        $sql = $includeInactive
            ? self::COLUMNS . ' ORDER BY lower(name) ASC'
            : self::COLUMNS . ' WHERE is_active = TRUE ORDER BY lower(name) ASC';

        return array_map(self::hydrate(...), $this->connection->select($sql));
    }

    public function forAnime(AnimeId $animeId): array
    {
        // The indexed join that replaces the legacy
        // `JSON_EXTRACT(save,'$.fansub') LIKE '%"12"%'` scan.
        $rows = $this->connection->select(
            <<<'SQL'
            SELECT u.id, u.slug, u.name, u.description, u.website_url, u.facebook_url,
                   u.video_url, u.email, u.is_active, u.created_at, u.updated_at
            FROM anime_uploaders au
                JOIN uploaders u ON u.id = au.uploader_id
            WHERE au.anime_id = :anime_id
            ORDER BY lower(u.name) ASC
            SQL,
            ['anime_id' => $animeId->value],
        );

        return array_map(self::hydrate(...), $rows);
    }

    public function save(Uploader $uploader): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO uploaders (id, slug, name, description, website_url, facebook_url,
                                   video_url, email, is_active, created_at, updated_at)
            VALUES (:id, :slug, :name, :description, :website_url, :facebook_url,
                    :video_url, :email, :is_active, :created_at, :updated_at)
            ON CONFLICT (id) DO UPDATE SET
                slug         = EXCLUDED.slug,
                name         = EXCLUDED.name,
                description  = EXCLUDED.description,
                website_url  = EXCLUDED.website_url,
                facebook_url = EXCLUDED.facebook_url,
                video_url    = EXCLUDED.video_url,
                email        = EXCLUDED.email,
                is_active    = EXCLUDED.is_active,
                updated_at   = EXCLUDED.updated_at
            SQL,
            [
                'id' => $uploader->id->value,
                'slug' => $uploader->slug()->value,
                'name' => $uploader->name(),
                'description' => $uploader->description(),
                'website_url' => $uploader->websiteUrl(),
                'facebook_url' => $uploader->facebookUrl(),
                'video_url' => $uploader->videoUrl(),
                'email' => $uploader->email(),
                'is_active' => $uploader->isActive(),
                'created_at' => $uploader->createdAt->format('Y-m-d H:i:sP'),
                'updated_at' => $uploader->updatedAt()->format('Y-m-d H:i:sP'),
            ],
        );
    }

    public function setAnimeUploaders(AnimeId $animeId, array $uploaderIds, \DateTimeImmutable $now): void
    {
        $this->connection->transaction(function () use ($animeId, $uploaderIds, $now): void {
            $this->connection->execute(
                'DELETE FROM anime_uploaders WHERE anime_id = :anime_id',
                ['anime_id' => $animeId->value],
            );

            foreach ($uploaderIds as $uploaderId) {
                $this->connection->execute(
                    <<<'SQL'
                    INSERT INTO anime_uploaders (anime_id, uploader_id, claimed_at)
                    VALUES (:anime_id, :uploader_id, :claimed_at)
                    ON CONFLICT DO NOTHING
                    SQL,
                    [
                        'anime_id' => $animeId->value,
                        'uploader_id' => $uploaderId->value,
                        'claimed_at' => $now->format('Y-m-d H:i:sP'),
                    ],
                );
            }
        });
    }

    public function slugExists(Slug $slug, ?UploaderId $excluding = null): bool
    {
        return (bool) $this->connection->scalar(
            <<<'SQL'
            SELECT EXISTS (
                SELECT 1 FROM uploaders
                WHERE slug = :slug AND (:excluding::uuid IS NULL OR id <> :excluding::uuid)
            )
            SQL,
            ['slug' => $slug->value, 'excluding' => $excluding?->value],
        );
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): Uploader
    {
        return Uploader::reconstitute(
            UploaderId::fromString((string) $row['id']),
            Slug::fromString((string) $row['slug']),
            (string) $row['name'],
            self::stringOrNull($row['description'] ?? null),
            self::stringOrNull($row['website_url'] ?? null),
            self::stringOrNull($row['facebook_url'] ?? null),
            self::stringOrNull($row['video_url'] ?? null),
            self::stringOrNull($row['email'] ?? null),
            (bool) $row['is_active'],
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['updated_at']),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
