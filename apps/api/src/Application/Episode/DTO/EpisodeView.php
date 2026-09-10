<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\DTO;

use Yume\Api\Domain\Episode\Entity\Episode;
use Yume\Api\Domain\Episode\Entity\EpisodeRelease;

final readonly class EpisodeView
{
    /** @param list<array<string, mixed>> $releases */
    public function __construct(
        public string $id,
        public string $animeId,
        public int $number,
        public ?string $title,
        public ?string $titleJapanese,
        public ?string $synopsis,
        public ?string $airedOn,
        public ?int $durationSeconds,
        public bool $isPublished,
        public array $releases,
        public string $createdAt,
    ) {
    }

    /**
     * @param list<EpisodeRelease> $releases
     * @param array<string, string> $uploaderNames uploader id => display name
     */
    public static function fromEntity(Episode $episode, array $releases = [], array $uploaderNames = []): self
    {
        return new self(
            $episode->id->value,
            $episode->animeId->value,
            $episode->number(),
            $episode->title(),
            $episode->titleJapanese(),
            $episode->synopsis(),
            $episode->airedOn()?->format('Y-m-d'),
            $episode->durationSeconds(),
            $episode->isPublished(),
            array_map(
                static fn (EpisodeRelease $release): array => [
                    'id' => $release->id,
                    'uploader_id' => $release->uploaderId->value,
                    'uploader_name' => $uploaderNames[$release->uploaderId->value] ?? null,
                    'language' => $release->language,
                    'kind' => $release->kind->value,
                    'host' => $release->host,
                    'url' => $release->url,
                ],
                $releases,
            ),
            $episode->createdAt->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'anime_id' => $this->animeId,
            'number' => $this->number,
            'title' => $this->title,
            'title_japanese' => $this->titleJapanese,
            'synopsis' => $this->synopsis,
            'aired_on' => $this->airedOn,
            'duration_seconds' => $this->durationSeconds,
            'is_published' => $this->isPublished,
            'releases' => $this->releases,
            'created_at' => $this->createdAt,
        ];
    }
}
