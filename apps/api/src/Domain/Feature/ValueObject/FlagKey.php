<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Feature\ValueObject;

final readonly class FlagKey implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (preg_match('/^[a-z][a-z0-9_]{1,98}[a-z0-9]$/', $value) !== 1) {
            throw new \InvalidArgumentException(sprintf('Feature flag key "%s" is invalid.', $value));
        }

        return new self($value);
    }

    /** Environment override name, e.g. `new_fansub_page` -> `FEATURE_NEW_FANSUB_PAGE`. */
    public function envName(): string
    {
        return 'FEATURE_' . strtoupper($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
