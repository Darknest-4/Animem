<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\ValueObject;

final readonly class PasswordHash implements \Stringable
{
    private function __construct(public string $value, public PasswordAlgorithm $algorithm)
    {
    }

    public static function argon2id(string $hash): self
    {
        if (!str_starts_with($hash, '$argon2id$')) {
            throw new \InvalidArgumentException('Not an Argon2id hash.');
        }

        return new self($hash, PasswordAlgorithm::Argon2id);
    }

    public static function legacySha256(string $hash): self
    {
        return new self($hash, PasswordAlgorithm::LegacySha256);
    }

    public static function fromStorage(string $hash, PasswordAlgorithm $algorithm): self
    {
        return new self($hash, $algorithm);
    }

    public function isLegacy(): bool
    {
        return $this->algorithm === PasswordAlgorithm::LegacySha256;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
