<?php

declare(strict_types=1);

namespace Yume\Contracts\Http;

interface ResponseInterface
{
    public function status(): int;

    /** @return array<string, string> */
    public function headers(): array;

    public function withHeader(string $name, string $value): static;

    public function withStatus(int $status): static;

    public function bodyAsString(): string;
}
