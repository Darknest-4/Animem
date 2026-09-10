<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Community\ValueObject;

final readonly class UploaderId implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new \InvalidArgumentException('Uploader id must be a UUID.');
        }

        return new self(strtolower($value));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
