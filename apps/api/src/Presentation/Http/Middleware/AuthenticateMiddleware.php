<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\Service\SessionPolicy;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Presentation\Http\Request\Request;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Api\Presentation\Http\Route;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Security\Token\TokenGenerator;

/**
 * Establishes the caller's identity from a session token.
 *
 * The token arrives either in the `yume_session` cookie (browser) or as a
 * bearer token (API client). It is looked up by SHA-256 digest, so a database
 * dump yields nothing usable, and it is validated on every request — the legacy
 * `logged()` function trusted a plain integer cookie and did a `SELECT ... WHERE
 * id = {$_COOKIE["userID"]}` with it, which was both the auth bypass and an
 * injection point.
 *
 * On a valid session the middleware also slides the expiry and, once per
 * rotation interval, issues a fresh token — limiting the value of a token
 * captured from a log or a shared machine.
 */
final class AuthenticateMiddleware implements MiddlewareInterface
{
    public const COOKIE_NAME = 'yume_session';

    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly SessionPolicy $policy,
        private readonly TokenGenerator $tokens,
        private readonly SecurityAuditor $auditor,
        private readonly ClockInterface $clock,
        private readonly bool $secureCookies = true,
    ) {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        $route = $request->attribute('route');
        $plainToken = $this->extractToken($request);

        if ($plainToken === null) {
            return $this->continueAsGuest($request, $next, $route);
        }

        $session = $this->sessions->findByTokenHash(
            TokenHash::fromString(TokenGenerator::hash($plainToken)),
        );

        $now = $this->clock->now();

        if ($session === null || !$session->isActive($now)) {
            return $this->continueAsGuest($request, $next, $route, expiredToken: true);
        }

        $ip = IpAddress::fromString($request->ip());
        $userAgent = UserAgent::fromString($request->userAgent());

        if ($this->policy->looksHijacked($session, $ip, $userAgent)) {
            $session->revoke($now, 'user_agent_changed');
            $this->sessions->save($session);

            $this->auditor->record(
                SecurityEventType::SessionHijackSuspected,
                $ip,
                $userAgent,
                $request->method(),
                $request->path(),
                $session->userId,
                100,
                ['session_id' => $session->id->value],
            );

            return $this->clearCookie(ProblemDetails::make(
                401,
                'auth.session_invalidated',
                'Your session was ended for security reasons. Please sign in again.',
            ));
        }

        $rotatedToken = null;
        if ($this->policy->shouldRotateToken($session, $now)) {
            $fresh = $this->tokens->generate();
            $session->rotateToken(TokenHash::fromString($fresh->hash));
            $rotatedToken = $fresh->plain;
        }

        $session->touch($now, $this->policy->idleExtensionSeconds);
        $this->sessions->save($session);

        $response = $next->handle(
            $request
                ->withAttribute('user_id', $session->userId->value)
                ->withAttribute('session_id', $session->id->value)
                ->withAttribute('authenticated', true),
        );

        if ($rotatedToken !== null && $response instanceof \Yume\Api\Presentation\Http\Response\JsonResponse) {
            return $response->withCookie($this->cookieLine($rotatedToken, $session->expiresAt()));
        }

        return $response;
    }

    private function continueAsGuest(
        RequestInterface $request,
        HandlerInterface $next,
        mixed $route,
        bool $expiredToken = false,
    ): ResponseInterface {
        if ($route instanceof Route && $route->requiresAuthentication()) {
            $response = ProblemDetails::make(
                401,
                $expiredToken ? 'auth.session_expired' : 'auth.required',
                $expiredToken
                    ? 'Your session has expired. Please sign in again.'
                    : 'Authentication is required for this endpoint.',
            );

            return $expiredToken ? $this->clearCookie($response) : $response;
        }

        return $next->handle($request->withAttribute('authenticated', false));
    }

    private function extractToken(RequestInterface $request): ?string
    {
        $authorization = $request->header('authorization');

        if ($authorization !== null && stripos($authorization, 'bearer ') === 0) {
            $token = trim(substr($authorization, 7));

            return $token === '' ? null : $token;
        }

        if ($request instanceof Request) {
            $cookie = $request->cookie(self::COOKIE_NAME);

            return $cookie === null || $cookie === '' ? null : $cookie;
        }

        return null;
    }

    public function cookieLine(string $token, \DateTimeImmutable $expiresAt): string
    {
        $parts = [
            self::COOKIE_NAME . '=' . $token,
            'Path=/',
            'Expires=' . $expiresAt->setTimezone(new \DateTimeZone('UTC'))->format('D, d M Y H:i:s \G\M\T'),
            'HttpOnly',
            'SameSite=Lax',
        ];

        if ($this->secureCookies) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }

    private function clearCookie(\Yume\Api\Presentation\Http\Response\JsonResponse $response): ResponseInterface
    {
        return $response->withCookie(
            self::COOKIE_NAME . '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; HttpOnly; SameSite=Lax'
            . ($this->secureCookies ? '; Secure' : ''),
        );
    }
}
