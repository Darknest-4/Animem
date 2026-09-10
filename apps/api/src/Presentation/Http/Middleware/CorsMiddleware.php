<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * CORS with an explicit origin allowlist.
 *
 * Never reflects an arbitrary Origin back with credentials enabled — that
 * combination hands every site on the internet an authenticated session.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /** @param list<string> $allowedOrigins */
    public function __construct(
        private readonly array $allowedOrigins = [],
        private readonly int $maxAgeSeconds = 600,
    ) {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        $origin = $request->header('origin');
        $allowed = $origin !== null && in_array($origin, $this->allowedOrigins, true);

        if ($request->method() === 'OPTIONS') {
            $preflight = JsonResponse::noContent();

            return $allowed ? $this->decorate($preflight, $origin) : $preflight;
        }

        $response = $next->handle($request);

        return $allowed ? $this->decorate($response, $origin) : $response;
    }

    private function decorate(ResponseInterface $response, string $origin): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-CSRF-Token, X-Request-Id')
            ->withHeader('Access-Control-Expose-Headers', 'X-Request-Id, RateLimit-Limit, RateLimit-Remaining, RateLimit-Reset')
            ->withHeader('Access-Control-Max-Age', (string) $this->maxAgeSeconds)
            // Caches must not serve one origin's CORS response to another.
            ->withHeader('Vary', 'Origin');
    }
}
