<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Request;

use Yume\Api\Presentation\Http\Request\Exception\ValidationFailedException;

/**
 * Minimal declarative validation for request bodies.
 *
 * Its job is shape and presence, not business rules: "is `email` a non-empty
 * string under 254 characters" belongs here, "is that email already registered"
 * belongs in the domain. Keeping the split sharp stops validation rules and
 * domain invariants from drifting apart.
 */
final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validated = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    /** @param array<string, mixed> $data */
    public static function for(array $data): self
    {
        return new self($data);
    }

    public function string(string $field, int $min = 1, int $max = 255, bool $required = true): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            if ($required) {
                $this->errors[$field][] = 'This field is required.';
            }

            return $this;
        }

        if (!is_string($value)) {
            $this->errors[$field][] = 'This field must be a string.';

            return $this;
        }

        $length = mb_strlen($value);

        if ($length < $min) {
            $this->errors[$field][] = sprintf('Must be at least %d characters.', $min);
        }

        if ($length > $max) {
            $this->errors[$field][] = sprintf('Must not exceed %d characters.', $max);
        }

        if (!isset($this->errors[$field])) {
            $this->validated[$field] = $value;
        }

        return $this;
    }

    public function email(string $field, bool $required = true): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            if ($required) {
                $this->errors[$field][] = 'This field is required.';
            }

            return $this;
        }

        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field][] = 'Must be a valid email address.';

            return $this;
        }

        $this->validated[$field] = $value;

        return $this;
    }

    public function boolean(string $field, bool $required = true): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null) {
            if ($required) {
                $this->errors[$field][] = 'This field is required.';
            }

            return $this;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            $this->errors[$field][] = 'Must be true or false.';

            return $this;
        }

        $this->validated[$field] = $parsed;

        return $this;
    }

    public function integer(string $field, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX, bool $required = true): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            if ($required) {
                $this->errors[$field][] = 'This field is required.';
            }

            return $this;
        }

        if (!is_numeric($value)) {
            $this->errors[$field][] = 'Must be a number.';

            return $this;
        }

        $parsed = (int) $value;

        if ($parsed < $min || $parsed > $max) {
            $this->errors[$field][] = sprintf('Must be between %d and %d.', $min, $max);

            return $this;
        }

        $this->validated[$field] = $parsed;

        return $this;
    }

    /**
     * Passwords are length-checked but never trimmed, lower-cased or escaped.
     * The legacy login ran the plaintext through htmlspecialchars() and
     * strip_tags() before hashing, silently mangling any password containing
     * < > & or ".
     */
    public function password(string $field, int $min = 12, int $max = 4096): self
    {
        $value = $this->data[$field] ?? null;

        if (!is_string($value) || $value === '') {
            $this->errors[$field][] = 'This field is required.';

            return $this;
        }

        $length = mb_strlen($value);

        if ($length < $min) {
            $this->errors[$field][] = sprintf('Must be at least %d characters.', $min);
        }

        if ($length > $max) {
            $this->errors[$field][] = sprintf('Must not exceed %d characters.', $max);
        }

        if (!isset($this->errors[$field])) {
            $this->validated[$field] = $value;
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     * @throws ValidationFailedException
     */
    public function validated(): array
    {
        if ($this->fails()) {
            throw new ValidationFailedException($this->errors);
        }

        return $this->validated;
    }
}
