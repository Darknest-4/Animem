<?php

declare(strict_types=1);

namespace Yume\Tests\Security;

use PHPUnit\Framework\TestCase;
use Yume\Api\Presentation\Http\Route;
use Yume\Api\Presentation\Http\Router;

/**
 * The deny-by-default guarantee.
 *
 * In the legacy site, forgetting a permission check produced a silently open
 * admin URL — `html/admin/.htaccess` had its entire auth block commented out and
 * nothing noticed. Here the same mistake must stop the application from booting.
 */
final class RouteAccessDeclarationTest extends TestCase
{
    public function testARouteWithoutAnAccessDeclarationRefusesToCompile(): void
    {
        $router = new Router();
        $router->get('/api/v1/secrets', [DummyController::class, 'index'], 'secrets.index');
        // Deliberately no ->can(), ->authenticated() or ->public().

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/without an access rule/');

        $router->compile();
    }

    public function testEveryRealRouteDeclaresItsAccessRule(): void
    {
        $router = $this->applicationRouter();

        foreach ($router->routes() as $route) {
            self::assertTrue(
                $route->accessDeclared(),
                sprintf('%s %s has no access declaration', $route->method, $route->pattern),
            );
        }
    }

    /**
     * Every /admin route must require a permission. "Authenticated" is not enough:
     * that would let any registered member into the moderation tools.
     */
    public function testAdminRoutesRequireAnExplicitPermission(): void
    {
        foreach ($this->applicationRouter()->routes() as $route) {
            if (!str_contains($route->pattern, '/admin')) {
                continue;
            }

            self::assertFalse($route->isPublic(), $route->pattern . ' is public');
            self::assertNotSame(
                [],
                $route->permissions(),
                $route->pattern . ' is admin-scoped but requires no permission',
            );
            self::assertContains(
                'admin.access',
                $route->permissions(),
                $route->pattern . ' does not require admin.access',
            );
        }
    }

    /** Authentication endpoints must carry a rate-limit policy; they are the attacked ones. */
    public function testAuthenticationEndpointsAreRateLimited(): void
    {
        $unlimited = [];

        foreach ($this->applicationRouter()->routes() as $route) {
            $isCredentialEndpoint = $route->method === 'POST'
                && (str_contains($route->pattern, '/auth/login')
                    || str_contains($route->pattern, '/auth/register')
                    || str_contains($route->pattern, '/auth/password'));

            if ($isCredentialEndpoint && $route->rateLimitPolicy() === null) {
                $unlimited[] = $route->pattern;
            }
        }

        self::assertSame([], $unlimited, 'credential endpoints without a rate limit: ' . implode(', ', $unlimited));
    }

    /**
     * A CSRF exemption is only defensible where no cookie session exists yet.
     * Any other exempt route would be a cross-site-writable endpoint.
     */
    public function testCsrfExemptionsAreLimitedToPreSessionEndpoints(): void
    {
        $allowed = ['/api/v1/auth/login', '/api/v1/auth/register'];

        foreach ($this->applicationRouter()->routes() as $route) {
            if ($route->isCsrfExempt()) {
                self::assertContains($route->pattern, $allowed, $route->pattern . ' is CSRF-exempt without justification');
            }
        }
    }

    public function testPublicRoutesAreAnExplicitlyReviewedList(): void
    {
        $expected = [
            'GET /health',
            'GET /health/ready',
            'POST /api/v1/auth/register',
            'POST /api/v1/auth/login',
            'GET /api/v1/features',
        ];

        $actual = [];
        foreach ($this->applicationRouter()->routes() as $route) {
            if ($route->isPublic()) {
                $actual[] = $route->method . ' ' . $route->pattern;
            }
        }

        sort($expected);
        sort($actual);

        // Adding a public route should require editing this list, which forces a
        // reviewer to look at it.
        self::assertSame($expected, $actual);
    }

    private function applicationRouter(): Router
    {
        $router = new Router();
        (require dirname(__DIR__, 2) . '/api/bootstrap/routes.php')($router);
        $router->compile();

        return $router;
    }
}

final class DummyController
{
    /** @param array<string, string> $parameters */
    public function index(mixed $request, array $parameters = []): void
    {
    }
}
