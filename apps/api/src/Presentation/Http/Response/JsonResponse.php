<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Response;

use Yume\Contracts\Http\ResponseInterface;
use Yume\Shared\Support\Json;

final class JsonResponse implements ResponseInterface
{
    /** @var list<string> raw Set-Cookie header lines */
    private array $cookies = [];

    /** @param array<string, string> $headers */
    private function __construct(
        private readonly mixed $payload,
        private readonly int $status,
        private array $headers = [],
    ) {
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
    }

    public static function ok(mixed $payload, array $headers = []): self
    {
        return new self($payload, 200, $headers);
    }

    public static function created(mixed $payload, array $headers = []): self
    {
        return new self($payload, 201, $headers);
    }

    public static function noContent(): self
    {
        return new self(null, 204);
    }

    /** @param array<string, string> $headers */
    public static function withStatusAndPayload(int $status, mixed $payload, array $headers = []): self
    {
        return new self($payload, $status, $headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    /** @return list<string> */
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): static
    {
        $clone = clone $this;
        foreach ($headers as $name => $value) {
            $clone->headers[$name] = $value;
        }

        return $clone;
    }

    public function withCookie(string $rawSetCookieLine): static
    {
        $clone = clone $this;
        $clone->cookies[] = $rawSetCookieLine;

        return $clone;
    }

    public function withStatus(int $status): static
    {
        $clone = new self($this->payload, $status, $this->headers);
        foreach ($this->cookies as $cookie) {
            $clone->cookies[] = $cookie;
        }

        return $clone;
    }

    public function bodyAsString(): string
    {
        if ($this->status === 204 || $this->payload === null) {
            return '';
        }

        return Json::encode($this->payload);
    }
}
