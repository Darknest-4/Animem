<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http;

/**
 * Route table with deny-by-default access declaration.
 *
 * {@see compile()} runs once at boot and refuses to start if any route omits its
 * access declaration. That inversion is the whole point: in the legacy codebase
 * a forgotten permission check produced a silently open admin URL, here it
 * produces a container that will not boot.
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    private bool $compiled = false;

    /** @param array{0: class-string, 1: string} $handler */
    public function get(string $pattern, array $handler, string $name = ''): Route
    {
        return $this->add('GET', $pattern, $handler, $name);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $pattern, array $handler, string $name = ''): Route
    {
        return $this->add('POST', $pattern, $handler, $name);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function put(string $pattern, array $handler, string $name = ''): Route
    {
        return $this->add('PUT', $pattern, $handler, $name);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function patch(string $pattern, array $handler, string $name = ''): Route
    {
        return $this->add('PATCH', $pattern, $handler, $name);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function delete(string $pattern, array $handler, string $name = ''): Route
    {
        return $this->add('DELETE', $pattern, $handler, $name);
    }

    /** @param array{0: class-string, 1: string} $handler */
    private function add(string $method, string $pattern, array $handler, string $name): Route
    {
        $route = new Route($method, $pattern, $handler, $name !== '' ? $name : $method . ' ' . $pattern);
        $this->routes[] = $route;

        return $route;
    }

    /** @throws \LogicException when a route has no access declaration */
    public function compile(): void
    {
        $undeclared = [];

        foreach ($this->routes as $route) {
            if (!$route->accessDeclared()) {
                $undeclared[] = $route->method . ' ' . $route->pattern;
            }
        }

        if ($undeclared !== []) {
            throw new \LogicException(sprintf(
                "Route(s) declared without an access rule: %s.\n"
                . 'Every route must call ->can(...), ->authenticated() or ->public().',
                implode(', ', $undeclared),
            ));
        }

        $this->compiled = true;
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes;
    }

    public function match(string $method, string $path): MatchResult
    {
        if (!$this->compiled) {
            throw new \LogicException('Router::compile() must run before matching.');
        }

        $pathMatched = false;
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route->regex, $path, $matches) !== 1) {
                continue;
            }

            $pathMatched = true;
            $allowedMethods[] = $route->method;

            if ($route->method !== $method) {
                continue;
            }

            $parameters = [];
            foreach ($route->parameterNames as $index => $name) {
                $parameters[$name] = $matches[$index + 1] ?? '';
            }

            return MatchResult::found($route, $parameters);
        }

        // A path that exists under another verb is 405, not 404: returning 404
        // would hide the resource and make the API harder to use correctly.
        return $pathMatched
            ? MatchResult::methodNotAllowed(array_values(array_unique($allowedMethods)))
            : MatchResult::notFound();
    }
}
