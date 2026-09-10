<?php

declare(strict_types=1);

namespace Yume\Tests\Security;

use PHPUnit\Framework\TestCase;
use Yume\Api\Presentation\Http\Middleware\AuthenticateMiddleware;
use Yume\Api\Presentation\Http\Middleware\AuthorizeMiddleware;
use Yume\Api\Presentation\Http\Middleware\ClientIdentityMiddleware;
use Yume\Api\Presentation\Http\Middleware\CsrfMiddleware;
use Yume\Api\Presentation\Http\Middleware\ErrorHandlerMiddleware;
use Yume\Api\Presentation\Http\Middleware\RateLimitMiddleware;
use Yume\Api\Presentation\Http\Middleware\RouteResolverMiddleware;
use Yume\Api\Presentation\Http\Middleware\SecurityGateMiddleware;

/**
 * The middleware order is a security control, not a style choice.
 *
 * Reordering two entries can silently disable a gate — for example, running
 * AuthorizeMiddleware before AuthenticateMiddleware would evaluate every request
 * as anonymous, and running SecurityGateMiddleware after AuthenticateMiddleware
 * would let a banned address spend Argon2id cycles on login attempts.
 */
final class MiddlewareOrderTest extends TestCase
{
    /** @var list<string> */
    private array $order;

    protected function setUp(): void
    {
        $providers = file_get_contents(dirname(__DIR__, 2) . '/api/bootstrap/providers.php');
        self::assertIsString($providers);

        preg_match_all('/^\s+([A-Za-z]+Middleware)::class,$/m', $providers, $matches);

        $this->order = $matches[1];
        self::assertNotEmpty($this->order, 'could not read the middleware order from providers.php');
    }

    public function testErrorHandlingIsOutermostSoNothingLeaksAStackTrace(): void
    {
        self::assertSame($this->shortName(ErrorHandlerMiddleware::class), $this->order[0]);
    }

    public function testAuthorizationIsTheLastGateBeforeTheController(): void
    {
        self::assertSame($this->shortName(AuthorizeMiddleware::class), end($this->order));
    }

    public function testAuthenticationRunsBeforeAuthorization(): void
    {
        self::assertLessThan(
            $this->positionOf(AuthorizeMiddleware::class),
            $this->positionOf(AuthenticateMiddleware::class),
            'authorising before authenticating would evaluate every request as anonymous',
        );
    }

    public function testClientIdentityIsEstablishedBeforeAnySecurityDecision(): void
    {
        $identity = $this->positionOf(ClientIdentityMiddleware::class);

        self::assertLessThan($this->positionOf(SecurityGateMiddleware::class), $identity);
        self::assertLessThan($this->positionOf(RateLimitMiddleware::class), $identity);
        self::assertLessThan($this->positionOf(AuthenticateMiddleware::class), $identity);
    }

    public function testBansAndRiskAreCheckedBeforeExpensiveWork(): void
    {
        self::assertLessThan(
            $this->positionOf(AuthenticateMiddleware::class),
            $this->positionOf(SecurityGateMiddleware::class),
            'a blocked client must not reach password verification',
        );
    }

    public function testRouteIsResolvedBeforeTheGatesThatReadItsDeclaration(): void
    {
        $resolver = $this->positionOf(RouteResolverMiddleware::class);

        self::assertLessThan($this->positionOf(RateLimitMiddleware::class), $resolver);
        self::assertLessThan($this->positionOf(AuthenticateMiddleware::class), $resolver);
        self::assertLessThan($this->positionOf(AuthorizeMiddleware::class), $resolver);
    }

    public function testCsrfRunsAfterAuthenticationBecauseItBindsToTheSession(): void
    {
        self::assertLessThan(
            $this->positionOf(CsrfMiddleware::class),
            $this->positionOf(AuthenticateMiddleware::class),
        );
    }

    private function positionOf(string $class): int
    {
        $position = array_search($this->shortName($class), $this->order, true);

        self::assertIsInt($position, $class . ' is not in the middleware pipeline');

        return $position;
    }

    private function shortName(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }
}
