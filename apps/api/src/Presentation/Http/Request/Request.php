<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Request;

use Yume\Contracts\Http\RequestInterface;
use Yume\Shared\Support\Json;

/**
 * An immutable snapshot of one HTTP request.
 *
 * Superglobals are read exactly once, in {@see fromGlobals()}. Nothing deeper in
 * the stack touches $_GET, $_POST, $_SERVER or $_COOKIE, which is what makes the
 * whole pipeline testable without a web server.
 */
final class Request implements RequestInterface
{
    /**
     * @param array<string, string> $headers lower-cased names
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $cookies
     * @param array<string, mixed> $attributes
     */
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers,
        private readonly array $query,
        private readonly array $body,
        private readonly array $cookies,
        private readonly string $ip,
        private readonly array $attributes = [],
    ) {
    }

    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $query
     * @param array<string, string> $cookies
     */
    public static function fromGlobals(
        array $server,
        array $query,
        array $cookies,
        string $rawBody,
    ): self {
        $headers = self::extractHeaders($server);
        $path = self::extractPath((string) ($server['REQUEST_URI'] ?? '/'));

        return new self(
            strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $headers,
            $query,
            self::parseBody($headers['content-type'] ?? '', $rawBody),
            $cookies,
            (string) ($server['REMOTE_ADDR'] ?? '127.0.0.1'),
        );
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $cookies
     */
    public static function create(
        string $method,
        string $path,
        array $headers = [],
        array $query = [],
        array $body = [],
        array $cookies = [],
        string $ip = '127.0.0.1',
    ): self {
        $normalised = [];
        foreach ($headers as $name => $value) {
            $normalised[strtolower($name)] = $value;
        }

        return new self(strtoupper($method), $path, $normalised, $query, $body, $cookies, $ip);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function body(): array
    {
        return $this->body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function cookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function withAttribute(string $key, mixed $value): static
    {
        $clone = new self(
            $this->method,
            $this->path,
            $this->headers,
            $this->query,
            $this->body,
            $this->cookies,
            $this->ip,
            [...$this->attributes, $key => $value],
        );

        return $clone;
    }

    public function ip(): string
    {
        return $this->attribute('client_ip', $this->ip);
    }

    public function userAgent(): string
    {
        return $this->header('user-agent', '') ?? '';
    }

    public function isWrite(): bool
    {
        return in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            $key = (string) $key;

            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
                continue;
            }

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[strtolower(str_replace('_', '-', $key))] = (string) $value;
            }
        }

        return $headers;
    }

    private static function extractPath(string $requestUri): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        // Collapse duplicate slashes and drop the trailing one so that
        // /api/v1/auth/me and /api/v1/auth/me/ hit the same route.
        $path = '/' . trim(preg_replace('#/+#', '/', $path) ?? '/', '/');

        return $path === '' ? '/' : $path;
    }

    /** @return array<string, mixed> */
    private static function parseBody(string $contentType, string $rawBody): array
    {
        if ($rawBody === '') {
            return [];
        }

        if (str_contains($contentType, 'application/json')) {
            return Json::decodeToArrayOrEmpty($rawBody);
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($rawBody, $parsed);

            return $parsed;
        }

        return [];
    }
}
