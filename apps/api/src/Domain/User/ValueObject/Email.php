<?php

declare(strict_types=1);

namespace Yume\Api\Domain\User\ValueObject;

final readonly class Email implements \Stringable
{
    public const MAX_LENGTH = 254;

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $value = mb_strtolower(trim($value));

        if ($value === '' || mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Email address has an invalid length.');
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Email address is not valid.');
        }

        return new self($value);
    }

    public function domain(): string
    {
        return substr($this->value, strrpos($this->value, '@') + 1);
    }

    /** Stable lookup key: local-part dots and +tags collapsed for duplicate detection. */
    public function canonical(): string
    {
        [$local, $domain] = explode('@', $this->value, 2);

        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = str_replace('.', '', $local);
        }

        if (str_contains($local, '+')) {
            $local = substr($local, 0, strpos($local, '+'));
        }

        return $local . '@' . $domain;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
