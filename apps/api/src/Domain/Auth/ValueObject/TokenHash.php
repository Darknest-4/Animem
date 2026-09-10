<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\ValueObject;

/** SHA-256 digest of a bearer token. The plaintext never reaches the domain. */
final readonly class TokenHash implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[0-9a-f]{64}$/', $value) !== 1) {
            throw new \InvalidArgumentException('Token hash must be a 64 character SHA-256 digest.');
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
