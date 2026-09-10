<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Authorization\ValueObject;

/**
 * A permission slug in `resource.action` form, e.g. `datasheet.edit`.
 *
 * Wildcards are supported on the granting side only (`datasheet.*` grants every
 * action on datasheets, `*` grants everything). A *required* permission must
 * always be fully qualified, so a route can never accidentally ask for `*`.
 */
final readonly class PermissionSlug implements \Stringable
{
    private const PATTERN = '/^(?:\*|[a-z][a-z0-9_]*\.(?:\*|[a-z][a-z0-9_]*))$/';

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'Permission "%s" must look like "resource.action", "resource.*" or "*".',
                $value,
            ));
        }

        return new self($value);
    }

    public static function required(string $value): self
    {
        $slug = self::fromString($value);

        if ($slug->isWildcard()) {
            throw new \InvalidArgumentException('A route may not require a wildcard permission.');
        }

        return $slug;
    }

    public function isWildcard(): bool
    {
        return $this->value === '*' || str_ends_with($this->value, '.*');
    }

    public function resource(): string
    {
        return $this->value === '*' ? '*' : explode('.', $this->value, 2)[0];
    }

    /** Does this granted slug satisfy the given required slug? */
    public function grants(self $required): bool
    {
        if ($this->value === '*') {
            return true;
        }

        if ($this->value === $required->value) {
            return true;
        }

        return str_ends_with($this->value, '.*')
            && $required->resource() === $this->resource();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
