<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Presentation\Http\Request\Request;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Api\Presentation\Http\Route;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Security\Csrf\CsrfTokenManager;

/**
 * CSRF protection for cookie-authenticated writes.
 *
 * Only cookie sessions need it: a bearer token is not attached automatically by
 * the browser, so a cross-site form post cannot carry one. The check is skipped
 * for safe verbs and for routes explicitly marked withoutCsrf().
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public const HEADER = 'x-csrf-token';

    public function __construct(private readonly CsrfTokenManager $tokens)
    {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        if (!$request instanceof Request || !$request->isWrite()) {
            return $next->handle($request);
        }

        $route = $request->attribute('route');
        if ($route instanceof Route && $route->isCsrfExempt()) {
            return $next->handle($request);
        }

        // Bearer-authenticated callers are not exposed to CSRF.
        $authorization = $request->header('authorization');
        if ($authorization !== null && stripos($authorization, 'bearer ') === 0) {
            return $next->handle($request);
        }

        $sessionId = $request->attribute('session_id');
        if (!is_string($sessionId)) {
            // No cookie session: nothing for an attacker to ride.
            return $next->handle($request);
        }

        $token = $request->header(self::HEADER) ?? (string) ($request->body()['_csrf'] ?? '');

        if (!$this->tokens->isValid($sessionId, $token === '' ? null : $token)) {
            return ProblemDetails::make(
                403,
                'csrf.invalid',
                'The CSRF token is missing or invalid. Reload the page and try again.',
            );
        }

        return $next->handle($request);
    }
}
