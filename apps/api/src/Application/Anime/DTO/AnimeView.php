<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\DTO;

use Yume\Api\Domain\Anime\Entity\Anime;
use Yume\Api\Domain\Community\Entity\Uploader;

/** The read model for a catalogue entry. */
final readonly class AnimeView
{
    /**
     * @param list<string> $genres
     * @param list<string> $studios
     * @param list<array<string, mixed>> $uploaders
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $title,
        public ?string $titleEnglish,
        public ?string $titleJapanese,
        public ?string $synopsis,
        public string $mediaType,
        public string $status,
        public ?int $malId,
        public ?string $source,
        public ?string $ageRating,
        public ?int $episodeCount,
        public ?string $season,
        public ?int $seasonYear,
        public ?string $airedFrom,
        public ?string $airedTo,
        public ?float $score,
        public ?string $coverUrl,
        public bool $isPublished,
        public array $genres,
        public array $studios,
        public array $uploaders,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /** @param list<Uploader> $uploaders */
    public static function fromEntity(Anime $anime, array $uploaders = []): self
    {
        return new self(
            $anime->id->value,
            $anime->slug()->value,
            $anime->title(),
            $anime->titleEnglish(),
            $anime->titleJapanese(),
            $anime->synopsis(),
            $anime->mediaType()->value,
            $anime->status()->value,
            $anime->malId(),
            $anime->source(),
            $anime->ageRating(),
            $anime->episodeCount(),
            $anime->season()?->value,
            $anime->seasonYear(),
            $anime->airedFrom()?->format('Y-m-d'),
            $anime->airedTo()?->format('Y-m-d'),
            $anime->score(),
            $anime->coverUrl(),
            $anime->isPublished(),
            $anime->genreSlugs(),
            $anime->studioSlugs(),
            array_map(
                static fn (Uploader $uploader): array => [
                    'id' => $uploader->id->value,
                    'slug' => $uploader->slug()->value,
                    'name' => $uploader->name(),
                ],
                $uploaders,
            ),
            $anime->createdAt->format(\DateTimeInterface::ATOM),
            $anime->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** Trimmed shape for list responses: no synopsis, no relations. */
    public static function summaryFromEntity(Anime $anime): array
    {
        return [
            'id' => $anime->id->value,
            'slug' => $anime->slug()->value,
            'title' => $anime->title(),
            'media_type' => $anime->mediaType()->value,
            'status' => $anime->status()->value,
            'season' => $anime->season()?->value,
            'season_year' => $anime->seasonYear(),
            'score' => $anime->score(),
            'cover_url' => $anime->coverUrl(),
            'genres' => $anime->genreSlugs(),
            'is_published' => $anime->isPublished(),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'title_english' => $this->titleEnglish,
            'title_japanese' => $this->titleJapanese,
            'synopsis' => $this->synopsis,
            'media_type' => $this->mediaType,
            'status' => $this->status,
            'mal_id' => $this->malId,
            'source' => $this->source,
            'age_rating' => $this->ageRating,
            'episode_count' => $this->episodeCount,
            'season' => $this->season,
            'season_year' => $this->seasonYear,
            'aired_from' => $this->airedFrom,
            'aired_to' => $this->airedTo,
            'score' => $this->score,
            'cover_url' => $this->coverUrl,
            'is_published' => $this->isPublished,
            'genres' => $this->genres,
            'studios' => $this->studios,
            'uploaders' => $this->uploaders,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
