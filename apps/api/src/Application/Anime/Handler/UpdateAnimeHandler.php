<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Handler;

use Yume\Api\Application\Anime\Command\UpdateAnimeCommand;
use Yume\Api\Application\Anime\DTO\AnimeView;
use Yume\Api\Domain\Anime\Exception\AnimeNotFoundException;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Anime\ValueObject\AiringStatus;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\MediaType;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;
use Yume\Api\Domain\Community\ValueObject\UploaderId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Persistence\ConnectionInterface;

/**
 * Applies a partial update.
 *
 * Only keys actually present in the command are touched, so a client that sends
 * `{"score": 8.4}` cannot blank out the synopsis it never mentioned. Every
 * mutation goes through an entity method, so the invariants (score range,
 * airing-window order) hold regardless of what the caller sent.
 */
final class UpdateAnimeHandler
{
    public function __construct(
        private readonly AnimeRepositoryInterface $anime,
        private readonly UploaderRepositoryInterface $uploaders,
        private readonly ConnectionInterface $connection,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(UpdateAnimeCommand $command): AnimeView
    {
        $animeId = AnimeId::fromString($command->animeId);
        $anime = $this->anime->findById($animeId);

        if ($anime === null) {
            throw AnimeNotFoundException::withId($command->animeId);
        }

        $now = $this->clock->now();
        $fields = $command->fields;
        $has = static fn (string $key): bool => array_key_exists($key, $fields);

        if ($has('title')) {
            $anime->rename((string) $fields['title'], $now);
        }

        if ($has('slug')) {
            $anime->changeSlug(
                $this->anime->reserveSlug(Slug::fromString((string) $fields['slug']), $animeId),
                $now,
            );
        }

        if ($has('synopsis')) {
            $anime->describe(self::asNullableString($fields['synopsis']), $now);
        }

        if ($has('title_english') || $has('title_japanese')) {
            $anime->setAlternateTitles(
                $has('title_english') ? self::asNullableString($fields['title_english']) : $anime->titleEnglish(),
                $has('title_japanese') ? self::asNullableString($fields['title_japanese']) : $anime->titleJapanese(),
                $now,
            );
        }

        if ($has('media_type') || $has('status')) {
            $mediaType = $has('media_type')
                ? MediaType::tryFrom((string) $fields['media_type'])
                : $anime->mediaType();
            $status = $has('status')
                ? AiringStatus::tryFrom((string) $fields['status'])
                : $anime->status();

            if ($mediaType === null || $status === null) {
                throw new \InvalidArgumentException('media_type or status is not a recognised value.');
            }

            $anime->classify($mediaType, $status, $now);
        }

        if ($has('mal_id')) {
            $anime->linkToMyAnimeList(
                $fields['mal_id'] === null ? null : (int) $fields['mal_id'],
                $now,
            );
        }

        if ($has('aired_from') || $has('aired_to')) {
            $anime->setAiringWindow(
                $has('aired_from') ? self::asNullableDate($fields['aired_from']) : $anime->airedFrom(),
                $has('aired_to') ? self::asNullableDate($fields['aired_to']) : $anime->airedTo(),
                $now,
            );
        }

        if ($has('score')) {
            $anime->setScore($fields['score'] === null ? null : (float) $fields['score'], $now);
        }

        if ($has('episode_count')) {
            $anime->setEpisodeCount($fields['episode_count'] === null ? null : (int) $fields['episode_count'], $now);
        }

        if ($has('cover_url')) {
            $anime->setCoverUrl(self::asNullableString($fields['cover_url']), $now);
        }

        if ($has('source')) {
            $anime->setSource(self::asNullableString($fields['source']), $now);
        }

        if ($has('age_rating')) {
            $anime->setAgeRating(self::asNullableString($fields['age_rating']), $now);
        }

        if ($has('genres') && is_array($fields['genres'])) {
            $anime->setGenres(array_map('strval', $fields['genres']), $now);
        }

        if ($has('studios') && is_array($fields['studios'])) {
            $anime->setStudios(array_map('strval', $fields['studios']), $now);
        }

        $uploaderIds = null;
        if ($has('uploaders') && is_array($fields['uploaders'])) {
            $uploaderIds = array_map(
                static fn (mixed $id): UploaderId => UploaderId::fromString((string) $id),
                $fields['uploaders'],
            );
        }

        $this->connection->transaction(function () use ($anime, $animeId, $uploaderIds, $now): void {
            $this->anime->save($anime);

            if ($uploaderIds !== null) {
                $this->uploaders->setAnimeUploaders($animeId, $uploaderIds, $now);
            }
        });

        return AnimeView::fromEntity($anime, $this->uploaders->forAnime($animeId));
    }

    private static function asNullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private static function asNullableDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Dates must be in YYYY-MM-DD form.');
        }
    }
}
