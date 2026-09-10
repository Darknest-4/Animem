<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Shared\ValueObject;

final readonly class IpAddress implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException('Invalid IP address.');
        }

        return new self($value);
    }

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return filter_var(trim($value), FILTER_VALIDATE_IP) === false ? null : new self(trim($value));
    }

    public function isPrivate(): bool
    {
        return filter_var(
            $this->value,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }

    public function isV6(): bool
    {
        return str_contains($this->value, ':');
    }

    /** Coarse grouping used for rate limiting so a /64 rotation is not a free pass. */
    public function subnetKey(): string
    {
        if (!$this->isV6()) {
            $parts = explode('.', $this->value);

            return implode('.', array_slice($parts, 0, 3)) . '.0/24';
        }

        $expanded = bin2hex(inet_pton($this->value) ?: '');

        return substr($expanded, 0, 16) . '::/64';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
