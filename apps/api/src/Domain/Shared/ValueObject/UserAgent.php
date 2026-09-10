<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Shared\ValueObject;

final readonly class UserAgent implements \Stringable
{
    public const MAX_LENGTH = 512;

    private function __construct(public string $value)
    {
    }

    public static function fromString(?string $value): self
    {
        $value = trim($value ?? '');

        if ($value === '') {
            return new self('');
        }

        return new self(mb_substr($value, 0, self::MAX_LENGTH));
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function contains(string $needle): bool
    {
        return stripos($this->value, $needle) !== false;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
