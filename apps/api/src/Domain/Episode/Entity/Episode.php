<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Episode\Entity;

use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;
use Yume\Api\Domain\User\ValueObject\UserId;

final class Episode
{
    private function __construct(
        public readonly EpisodeId $id,
        public readonly AnimeId $animeId,
        private int $number,
        private ?string $title,
        private ?string $titleJapanese,
        private ?string $synopsis,
        private ?\DateTimeImmutable $airedOn,
        private ?int $durationSeconds,
        private bool $isPublished,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        public readonly ?UserId $createdBy,
    ) {
    }

    public static function create(
        EpisodeId $id,
        AnimeId $animeId,
        int $number,
        \DateTimeImmutable $now,
        ?string $title = null,
        ?UserId $createdBy = null,
    ): self {
        if ($number < 1) {
            throw new \InvalidArgumentException('Episode number must be 1 or greater.');
        }

        return new self($id, $animeId, $number, $title, null, null, null, null, false, $now, $now, $createdBy);
    }

    public static function reconstitute(
        EpisodeId $id,
        AnimeId $animeId,
        int $number,
        ?string $title,
        ?string $titleJapanese,
        ?string $synopsis,
        ?\DateTimeImmutable $airedOn,
        ?int $durationSeconds,
        bool $isPublished,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?UserId $createdBy,
    ): self {
        return new self(
            $id, $animeId, $number, $title, $titleJapanese, $synopsis,
            $airedOn, $durationSeconds, $isPublished, $createdAt, $updatedAt, $createdBy,
        );
    }

    public function number(): int
    {
        return $this->number;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function titleJapanese(): ?string
    {
        return $this->titleJapanese;
    }

    public function synopsis(): ?string
    {
        return $this->synopsis;
    }

    public function airedOn(): ?\DateTimeImmutable
    {
        return $this->airedOn;
    }

    public function durationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function retitle(?string $title, ?string $titleJapanese, \DateTimeImmutable $now): void
    {
        $this->title = self::nullIfBlank($title);
        $this->titleJapanese = self::nullIfBlank($titleJapanese);
        $this->updatedAt = $now;
    }

    public function describe(?string $synopsis, \DateTimeImmutable $now): void
    {
        $this->synopsis = self::nullIfBlank($synopsis);
        $this->updatedAt = $now;
    }

    public function setAiredOn(?\DateTimeImmutable $airedOn, \DateTimeImmutable $now): void
    {
        $this->airedOn = $airedOn;
        $this->updatedAt = $now;
    }

    public function setDuration(?int $seconds, \DateTimeImmutable $now): void
    {
        if ($seconds !== null && $seconds < 1) {
            throw new \InvalidArgumentException('Duration must be positive.');
        }

        $this->durationSeconds = $seconds;
        $this->updatedAt = $now;
    }

    public function renumber(int $number, \DateTimeImmutable $now): void
    {
        if ($number < 1) {
            throw new \InvalidArgumentException('Episode number must be 1 or greater.');
        }

        $this->number = $number;
        $this->updatedAt = $now;
    }

    /**
     * An episode with no release is a listing with nothing behind it, so
     * publishing is gated on having at least one.
     */
    public function publish(int $releaseCount, \DateTimeImmutable $now): void
    {
        if ($releaseCount < 1) {
            throw new \DomainException('An episode cannot be published before it has at least one release.');
        }

        $this->isPublished = true;
        $this->updatedAt = $now;
    }

    public function unpublish(\DateTimeImmutable $now): void
    {
        $this->isPublished = false;
        $this->updatedAt = $now;
    }

    private static function nullIfBlank(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
