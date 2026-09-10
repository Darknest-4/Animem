<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Authorization\ValueObject;

/** The effective, flattened set of permissions granted to one actor. */
final readonly class PermissionSet implements \Countable
{
    /** @param list<PermissionSlug> $slugs */
    private function __construct(private array $slugs)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /** @param list<string> $slugs */
    public static function fromStrings(array $slugs): self
    {
        $unique = array_values(array_unique(array_map('strtolower', $slugs)));

        return new self(array_map(
            static fn (string $slug): PermissionSlug => PermissionSlug::fromString($slug),
            $unique,
        ));
    }

    public function allows(string $required): bool
    {
        $requiredSlug = PermissionSlug::required($required);

        foreach ($this->slugs as $granted) {
            if ($granted->grants($requiredSlug)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $required */
    public function allowsAll(array $required): bool
    {
        foreach ($required as $slug) {
            if (!$this->allows($slug)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    public function toStrings(): array
    {
        return array_map(static fn (PermissionSlug $slug): string => $slug->value, $this->slugs);
    }

    public function count(): int
    {
        return count($this->slugs);
    }
}
