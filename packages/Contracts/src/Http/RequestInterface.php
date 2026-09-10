<?php

declare(strict_types=1);

namespace Yume\Contracts\Http;

interface RequestInterface
{
    public function method(): string;

    public function path(): string;

    public function header(string $name, ?string $default = null): ?string;

    public function query(string $key, mixed $default = null): mixed;

    /** @return array<string, mixed> */
    public function body(): array;

    public function attribute(string $key, mixed $default = null): mixed;

    public function withAttribute(string $key, mixed $value): static;

    public function ip(): string;

    public function userAgent(): string;
}
