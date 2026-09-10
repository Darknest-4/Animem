<?php

declare(strict_types=1);

namespace Yume\Api\Application\Community\DTO;

use Yume\Api\Domain\Community\Entity\Uploader;

final readonly class UploaderView
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public ?string $description,
        public ?string $websiteUrl,
        public ?string $facebookUrl,
        public ?string $videoUrl,
        public bool $isActive,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Uploader $uploader): self
    {
        return new self(
            $uploader->id->value,
            $uploader->slug()->value,
            $uploader->name(),
            $uploader->description(),
            $uploader->websiteUrl(),
            $uploader->facebookUrl(),
            $uploader->videoUrl(),
            $uploader->isActive(),
            $uploader->createdAt->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * The contact address is deliberately absent: it is stored for moderators,
     * not published to every visitor who lists the groups.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'website_url' => $this->websiteUrl,
            'facebook_url' => $this->facebookUrl,
            'video_url' => $this->videoUrl,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
        ];
    }
}
