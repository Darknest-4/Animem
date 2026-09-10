<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http;

/**
 * One route declaration.
 *
 * Access control is part of the declaration, not an afterthought inside the
 * controller: a route must call either can() or public() before the router will
 * accept it. See {@see Router::compile()} — an undeclared route is a boot-time
 * failure, so "we forgot the permission check" cannot reach production.
 */
final class Route
{
    /** @var list<string> */
    private array $permissions = [];

    private bool $explicitlyPublic = false;

    private bool $accessDeclared = false;

    private ?string $rateLimitPolicy = null;

    private bool $csrfExempt = false;

    private ?string $featureFlag = null;

    /** @var list<string> */
    public readonly array $parameterNames;

    public readonly string $regex;

    /** @param array{0: class-string, 1: string} $handler */
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly array $handler,
        public readonly string $name,
    ) {
        [$this->regex, $this->parameterNames] = self::compilePattern($pattern);
    }

    /** Requires every listed permission (AND, not OR). */
    public function can(string ...$permissions): self
    {
        foreach ($permissions as $permission) {
            $this->permissions[] = $permission;
        }

        $this->accessDeclared = true;

        return $this;
    }

    /** Explicitly reachable without authentication. Must be stated, never inferred. */
    public function public(): self
    {
        $this->explicitlyPublic = true;
        $this->accessDeclared = true;

        return $this;
    }

    /** Authenticated, but requiring no particular permission beyond a valid session. */
    public function authenticated(): self
    {
        $this->accessDeclared = true;

        return $this;
    }

    public function rateLimit(string $policyName): self
    {
        $this->rateLimitPolicy = $policyName;

        return $this;
    }

    /**
     * Only for endpoints authenticated by bearer token rather than by cookie —
     * those are not reachable by a cross-site form post, so a CSRF token adds
     * nothing. Never use it to silence a failing test.
     */
    public function withoutCsrf(): self
    {
        $this->csrfExempt = true;

        return $this;
    }

    /** Gates the whole route behind a feature flag; 404 when the flag is off. */
    public function behindFlag(string $flagKey): self
    {
        $this->featureFlag = $flagKey;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->explicitlyPublic;
    }

    public function requiresAuthentication(): bool
    {
        return !$this->explicitlyPublic;
    }

    public function accessDeclared(): bool
    {
        return $this->accessDeclared;
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return $this->permissions;
    }

    public function rateLimitPolicy(): ?string
    {
        return $this->rateLimitPolicy;
    }

    public function isCsrfExempt(): bool
    {
        return $this->csrfExempt;
    }

    public function featureFlag(): ?string
    {
        return $this->featureFlag;
    }

    /**
     * Turns `/api/v1/users/{id}` into an anchored regex plus its parameter names.
     *
     * @return array{0: string, 1: list<string>}
     */
    private static function compilePattern(string $pattern): array
    {
        $names = [];

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $matches) use (&$names): string {
                $names[] = $matches[1];
                $constraint = $matches[2] ?? '[^/]+';

                return '(' . $constraint . ')';
            },
            $pattern,
        );

        return ['#^' . $regex . '$#', $names];
    }
}
