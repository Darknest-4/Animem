<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http;

final readonly class MatchResult
{
    /**
     * @param array<string, string> $parameters
     * @param list<string> $allowedMethods
     */
    private function __construct(
        public bool $matched,
        public ?Route $route,
        public array $parameters,
        public bool $methodNotAllowed,
        public array $allowedMethods,
    ) {
    }

    /** @param array<string, string> $parameters */
    public static function found(Route $route, array $parameters): self
    {
        return new self(true, $route, $parameters, false, []);
    }

    public static function notFound(): self
    {
        return new self(false, null, [], false, []);
    }

    /** @param list<string> $allowedMethods */
    public static function methodNotAllowed(array $allowedMethods): self
    {
        return new self(false, null, [], true, $allowedMethods);
    }
}
