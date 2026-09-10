<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\ValueObject;

final readonly class AnimeId implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new \InvalidArgumentException('Anime id must be a UUID.');
        }

        return new self(strtolower($value));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
