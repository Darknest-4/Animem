<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\Entity;

use Yume\Api\Domain\Anime\ValueObject\AiringStatus;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\MediaType;
use Yume\Api\Domain\Anime\ValueObject\Season;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\User\ValueObject\UserId;

/**
 * A catalogue entry.
 *
 * Drafts (is_published = false) are the editorial workflow the legacy site
 * lacked: it wrote straight to the live `datasheet` table, so a half-filled
 * entry was immediately public.
 */
final class Anime
{
    /** @param list<string> $genreSlugs @param list<string> $studioSlugs */
    private function __construct(
        public readonly AnimeId $id,
        private Slug $slug,
        private string $title,
        private ?string $titleEnglish,
        private ?string $titleJapanese,
        private ?string $synopsis,
        private MediaType $mediaType,
        private AiringStatus $status,
        private ?int $malId,
        private ?string $source,
        private ?string $ageRating,
        private ?int $episodeCount,
        private ?Season $season,
        private ?int $seasonYear,
        private ?\DateTimeImmutable $airedFrom,
        private ?\DateTimeImmutable $airedTo,
        private ?float $score,
        private ?string $coverUrl,
        private bool $isPublished,
        private array $genreSlugs,
        private array $studioSlugs,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        public readonly ?UserId $createdBy,
    ) {
    }

    public static function draft(
        AnimeId $id,
        Slug $slug,
        string $title,
        MediaType $mediaType,
        \DateTimeImmutable $now,
        ?UserId $createdBy = null,
    ): self {
        return new self(
            $id,
            $slug,
            $title,
            null,
            null,
            null,
            $mediaType,
            AiringStatus::Upcoming,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            false,
            [],
            [],
            $now,
            $now,
            $createdBy,
        );
    }

    /**
     * @param list<string> $genreSlugs
     * @param list<string> $studioSlugs
     */
    public static function reconstitute(
        AnimeId $id,
        Slug $slug,
        string $title,
        ?string $titleEnglish,
        ?string $titleJapanese,
        ?string $synopsis,
        MediaType $mediaType,
        AiringStatus $status,
        ?int $malId,
        ?string $source,
        ?string $ageRating,
        ?int $episodeCount,
        ?Season $season,
        ?int $seasonYear,
        ?\DateTimeImmutable $airedFrom,
        ?\DateTimeImmutable $airedTo,
        ?float $score,
        ?string $coverUrl,
        bool $isPublished,
        array $genreSlugs,
        array $studioSlugs,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?UserId $createdBy,
    ): self {
        return new self(
            $id, $slug, $title, $titleEnglish, $titleJapanese, $synopsis, $mediaType, $status,
            $malId, $source, $ageRating, $episodeCount, $season, $seasonYear, $airedFrom, $airedTo,
            $score, $coverUrl, $isPublished, $genreSlugs, $studioSlugs, $createdAt, $updatedAt, $createdBy,
        );
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function titleEnglish(): ?string
    {
        return $this->titleEnglish;
    }

    public function titleJapanese(): ?string
    {
        return $this->titleJapanese;
    }

    public function synopsis(): ?string
    {
        return $this->synopsis;
    }

    public function mediaType(): MediaType
    {
        return $this->mediaType;
    }

    public function status(): AiringStatus
    {
        return $this->status;
    }

    public function malId(): ?int
    {
        return $this->malId;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function ageRating(): ?string
    {
        return $this->ageRating;
    }

    public function episodeCount(): ?int
    {
        return $this->episodeCount;
    }

    public function season(): ?Season
    {
        return $this->season;
    }

    public function seasonYear(): ?int
    {
        return $this->seasonYear;
    }

    public function airedFrom(): ?\DateTimeImmutable
    {
        return $this->airedFrom;
    }

    public function airedTo(): ?\DateTimeImmutable
    {
        return $this->airedTo;
    }

    public function score(): ?float
    {
        return $this->score;
    }

    public function coverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    /** @return list<string> */
    public function genreSlugs(): array
    {
        return $this->genreSlugs;
    }

    /** @return list<string> */
    public function studioSlugs(): array
    {
        return $this->studioSlugs;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function rename(string $title, \DateTimeImmutable $now): void
    {
        $title = trim($title);

        if ($title === '') {
            throw new \InvalidArgumentException('Title cannot be empty.');
        }

        $this->title = $title;
        $this->touch($now);
    }

    public function changeSlug(Slug $slug, \DateTimeImmutable $now): void
    {
        $this->slug = $slug;
        $this->touch($now);
    }

    public function describe(?string $synopsis, \DateTimeImmutable $now): void
    {
        $this->synopsis = $synopsis === null || trim($synopsis) === '' ? null : trim($synopsis);
        $this->touch($now);
    }

    public function setAlternateTitles(?string $english, ?string $japanese, \DateTimeImmutable $now): void
    {
        $this->titleEnglish = self::nullIfBlank($english);
        $this->titleJapanese = self::nullIfBlank($japanese);
        $this->touch($now);
    }

    public function classify(MediaType $mediaType, AiringStatus $status, \DateTimeImmutable $now): void
    {
        $this->mediaType = $mediaType;
        $this->status = $status;
        $this->touch($now);
    }

    public function linkToMyAnimeList(?int $malId, \DateTimeImmutable $now): void
    {
        if ($malId !== null && $malId < 1) {
            throw new \InvalidArgumentException('MyAnimeList id must be positive.');
        }

        $this->malId = $malId;
        $this->touch($now);
    }

    public function setAiringWindow(?\DateTimeImmutable $from, ?\DateTimeImmutable $to, \DateTimeImmutable $now): void
    {
        if ($from !== null && $to !== null && $to < $from) {
            throw new \InvalidArgumentException('The end of the airing window cannot precede its start.');
        }

        $this->airedFrom = $from;
        $this->airedTo = $to;

        // The season is derivable from the first air date, so it is derived
        // rather than stored twice and allowed to disagree.
        if ($from !== null) {
            $this->season = Season::forMonth((int) $from->format('n'));
            $this->seasonYear = (int) $from->format('Y');
        }

        $this->touch($now);
    }

    public function setScore(?float $score, \DateTimeImmutable $now): void
    {
        if ($score !== null && ($score < 0 || $score > 10)) {
            throw new \InvalidArgumentException('Score must be between 0 and 10.');
        }

        $this->score = $score;
        $this->touch($now);
    }

    public function setEpisodeCount(?int $count, \DateTimeImmutable $now): void
    {
        if ($count !== null && $count < 0) {
            throw new \InvalidArgumentException('Episode count cannot be negative.');
        }

        $this->episodeCount = $count;
        $this->touch($now);
    }

    public function setCoverUrl(?string $url, \DateTimeImmutable $now): void
    {
        $this->coverUrl = self::nullIfBlank($url);
        $this->touch($now);
    }

    public function setSource(?string $source, \DateTimeImmutable $now): void
    {
        $this->source = self::nullIfBlank($source);
        $this->touch($now);
    }

    public function setAgeRating(?string $rating, \DateTimeImmutable $now): void
    {
        $this->ageRating = self::nullIfBlank($rating);
        $this->touch($now);
    }

    /** @param list<string> $genreSlugs */
    public function setGenres(array $genreSlugs, \DateTimeImmutable $now): void
    {
        $this->genreSlugs = array_values(array_unique(array_map('strtolower', $genreSlugs)));
        $this->touch($now);
    }

    /** @param list<string> $studioSlugs */
    public function setStudios(array $studioSlugs, \DateTimeImmutable $now): void
    {
        $this->studioSlugs = array_values(array_unique(array_map('strtolower', $studioSlugs)));
        $this->touch($now);
    }

    /**
     * Fields a reader would notice were absent.
     *
     * Exposed rather than only checked inside publish() so the caller can name
     * them in an error response without parsing an exception message.
     *
     * @return list<string>
     */
    public function missingForPublication(): array
    {
        $missing = [];

        if (trim($this->title) === '') {
            $missing[] = 'title';
        }

        if ($this->synopsis === null) {
            $missing[] = 'synopsis';
        }

        if ($this->coverUrl === null) {
            $missing[] = 'cover_url';
        }

        return $missing;
    }

    public function canBePublished(): bool
    {
        return $this->missingForPublication() === [];
    }

    /**
     * @throws \DomainException when the entry is too incomplete to show anyone
     */
    public function publish(\DateTimeImmutable $now): void
    {
        $missing = $this->missingForPublication();

        if ($missing !== []) {
            throw new \DomainException('Cannot publish an entry missing: ' . implode(', ', $missing));
        }

        $this->isPublished = true;
        $this->touch($now);
    }

    public function unpublish(\DateTimeImmutable $now): void
    {
        $this->isPublished = false;
        $this->touch($now);
    }

    private function touch(\DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }

    private static function nullIfBlank(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
