<?php

declare(strict_types=1);

namespace Yume\Api\Domain\User\ValueObject;

final readonly class Username implements \Stringable
{
    public const MIN_LENGTH = 3;
    public const MAX_LENGTH = 32;
    private const PATTERN = '/^[a-zA-Z0-9](?:[a-zA-Z0-9_.-]{1,30})[a-zA-Z0-9]$/';

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);
        $length = mb_strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf(
                'Username must be between %d and %d characters.',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
            ));
        }

        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new \InvalidArgumentException(
                'Username may contain letters, digits, dot, dash and underscore, and must start and end with a letter or digit.',
            );
        }

        return new self($value);
    }

    /**
     * Case-insensitive uniqueness key with separators removed entirely.
     *
     * `kitsune`, `Kit.Sune`, `kit-sune` and `kit_sune` therefore collide and only
     * one of them can exist. That is deliberate: on a community site the cost of
     * refusing a slightly different spelling is far lower than the cost of
     * letting someone register a name that impersonates an existing member.
     */
    public function canonical(): string
    {
        return str_replace(['.', '-', '_'], '', mb_strtolower($this->value));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
