<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\ValueObject;

/**
 * URL-safe identifier derived from a title.
 *
 * Kept as a value object rather than a helper function because slug collisions
 * are a real editorial problem (two adaptations of the same work) and the
 * disambiguation rule belongs with the type.
 */
final readonly class Slug implements \Stringable
{
    public const MAX_LENGTH = 200;

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) !== 1 || mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid slug.', $value));
        }

        return new self($value);
    }

    /** Best-effort transliteration; Japanese titles usually arrive romanised already. */
    public static function fromTitle(string $title): self
    {
        $slug = mb_strtolower(trim($title));

        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        if (is_string($transliterated) && $transliterated !== '') {
            $slug = $transliterated;
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($slug)) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            // A title with no Latin characters at all still needs an identifier.
            $slug = 'anime-' . substr(hash('sha256', $title), 0, 10);
        }

        return new self(mb_substr($slug, 0, self::MAX_LENGTH));
    }

    /** Appends a discriminator: `cowboy-bebop` -> `cowboy-bebop-2`. */
    public function withSuffix(int $suffix): self
    {
        $base = mb_substr($this->value, 0, self::MAX_LENGTH - strlen((string) $suffix) - 1);

        return new self($base . '-' . $suffix);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
