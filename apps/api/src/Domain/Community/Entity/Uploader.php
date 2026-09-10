<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Community\Entity;

use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\Community\ValueObject\UploaderId;

/** A fansub group. */
final class Uploader
{
    private function __construct(
        public readonly UploaderId $id,
        private Slug $slug,
        private string $name,
        private ?string $description,
        private ?string $websiteUrl,
        private ?string $facebookUrl,
        private ?string $videoUrl,
        private ?string $email,
        private bool $isActive,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        UploaderId $id,
        Slug $slug,
        string $name,
        \DateTimeImmutable $now,
    ): self {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('A fansub group needs a name.');
        }

        return new self($id, $slug, $name, null, null, null, null, null, true, $now, $now);
    }

    public static function reconstitute(
        UploaderId $id,
        Slug $slug,
        string $name,
        ?string $description,
        ?string $websiteUrl,
        ?string $facebookUrl,
        ?string $videoUrl,
        ?string $email,
        bool $isActive,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id, $slug, $name, $description, $websiteUrl, $facebookUrl,
            $videoUrl, $email, $isActive, $createdAt, $updatedAt,
        );
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function websiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function facebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function videoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function rename(string $name, \DateTimeImmutable $now): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('A fansub group needs a name.');
        }

        $this->name = $name;
        $this->updatedAt = $now;
    }

    public function describe(?string $description, \DateTimeImmutable $now): void
    {
        $this->description = self::nullIfBlank($description);
        $this->updatedAt = $now;
    }

    public function setLinks(?string $website, ?string $facebook, ?string $video, \DateTimeImmutable $now): void
    {
        $this->websiteUrl = self::validUrlOrNull($website);
        $this->facebookUrl = self::validUrlOrNull($facebook);
        $this->videoUrl = self::validUrlOrNull($video);
        $this->updatedAt = $now;
    }

    public function setEmail(?string $email, \DateTimeImmutable $now): void
    {
        $email = self::nullIfBlank($email);

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('The contact email address is not valid.');
        }

        $this->email = $email === null ? null : mb_strtolower($email);
        $this->updatedAt = $now;
    }

    /** Groups that stop translating are deactivated, not deleted: their releases stay attributed. */
    public function deactivate(\DateTimeImmutable $now): void
    {
        $this->isActive = false;
        $this->updatedAt = $now;
    }

    public function reactivate(\DateTimeImmutable $now): void
    {
        $this->isActive = true;
        $this->updatedAt = $now;
    }

    private static function validUrlOrNull(?string $url): ?string
    {
        $url = self::nullIfBlank($url);

        if ($url === null) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid URL.', $url));
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        // javascript: and data: URLs in a link the site renders are an XSS vector.
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Links must use http or https.');
        }

        return $url;
    }

    private static function nullIfBlank(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
