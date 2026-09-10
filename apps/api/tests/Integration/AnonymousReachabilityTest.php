<?php

declare(strict_types=1);

namespace Yume\Tests\Integration;

use Yume\Api\Domain\Authorization\ValueObject\PermissionSet;
use Yume\Api\Infrastructure\Persistence\Repository\PdoRoleRepository;
use Yume\Api\Presentation\Http\Route;
use Yume\Api\Presentation\Http\Router;
use Yume\Tests\Support\DatabaseTestCase;

/**
 * What an anonymous visitor can reach.
 *
 * A `can()` route does not force a session: the `guest` role holds a real
 * permission set, and AuthorizeMiddleware decides. That makes the public surface
 * a product of two files — bootstrap/routes.php and the role seed — neither of
 * which shows the whole picture alone. This test computes the intersection and
 * pins it, so widening the guest role or adding a route cannot quietly open
 * something nobody reviewed.
 */
final class AnonymousReachabilityTest extends DatabaseTestCase
{
    public function testTheAnonymouslyReachableSurfaceIsTheReviewedSet(): void
    {
        $guest = (new PdoRoleRepository($this->connection))->permissionsForGuest();

        $reachable = [];
        foreach ($this->applicationRouter()->routes() as $route) {
            if ($this->isReachableAnonymously($route, $guest)) {
                $reachable[] = $route->method . ' ' . $route->pattern;
            }
        }

        sort($reachable);

        $expected = [
            // Platform
            'GET /health',
            'GET /health/ready',
            // Pre-session authentication
            'POST /api/v1/auth/register',
            'POST /api/v1/auth/login',
            'POST /api/v1/auth/email/verify',
            'POST /api/v1/auth/email/resend',
            'POST /api/v1/auth/password/forgot',
            'POST /api/v1/auth/password/reset',
            // The front end needs to know what to render before sign-in
            'GET /api/v1/features',
            // The catalogue is meant to be readable; `guest` holds these three
            'GET /api/v1/anime',
            'GET /api/v1/anime/{identifier:[A-Za-z0-9-]{1,200}}',
            'GET /api/v1/anime/{id:[0-9a-fA-F-]{36}}/episodes',
            'GET /api/v1/episodes/allowed-hosts',
            'GET /api/v1/uploaders',
            'GET /api/v1/uploaders/{identifier:[A-Za-z0-9-]{1,200}}',
        ];
        sort($expected);

        self::assertSame($expected, $reachable);
    }

    /** Nothing that changes state may be reachable without a session. */
    public function testNoWriteEndpointIsReachableAnonymouslyExceptPreSessionAuth(): void
    {
        $guest = (new PdoRoleRepository($this->connection))->permissionsForGuest();
        $allowed = [
            '/api/v1/auth/register',
            '/api/v1/auth/login',
            '/api/v1/auth/email/verify',
            '/api/v1/auth/email/resend',
            '/api/v1/auth/password/forgot',
            '/api/v1/auth/password/reset',
        ];

        foreach ($this->applicationRouter()->routes() as $route) {
            if (in_array($route->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
                continue;
            }

            if (!$this->isReachableAnonymously($route, $guest)) {
                continue;
            }

            self::assertContains(
                $route->pattern,
                $allowed,
                sprintf('%s %s writes state and is reachable without a session', $route->method, $route->pattern),
            );
        }
    }

    /** No /admin route may be reachable without a session, whatever guest holds. */
    public function testNoAdminRouteIsReachableAnonymously(): void
    {
        $guest = (new PdoRoleRepository($this->connection))->permissionsForGuest();

        foreach ($this->applicationRouter()->routes() as $route) {
            if (!str_contains($route->pattern, '/admin')) {
                continue;
            }

            self::assertFalse(
                $this->isReachableAnonymously($route, $guest),
                $route->pattern . ' is reachable without signing in',
            );
        }
    }

    private function isReachableAnonymously(Route $route, PermissionSet $guest): bool
    {
        if ($route->isPublic()) {
            return true;
        }

        if ($route->requiresAuthentication()) {
            return false;
        }

        // A can() route without an explicit authentication requirement is
        // decided entirely by whether the guest role satisfies it.
        return $route->permissions() !== [] && $guest->allowsAll($route->permissions());
    }

    private function applicationRouter(): Router
    {
        $router = new Router();
        (require dirname(__DIR__, 2) . '/api/bootstrap/routes.php')($router);
        $router->compile();

        return $router;
    }
}
