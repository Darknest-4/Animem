<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * Response hardening headers. The legacy site sent none of these.
 *
 * They are set here rather than in nginx so they survive a change of edge proxy
 * and are covered by the test suite.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly bool $enableHsts = true)
    {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        $response = $next->handle($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-site',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=()',
            // A JSON API renders nothing, so the strictest possible policy applies.
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'",
            'Cache-Control' => 'no-store',
        ];

        if ($this->enableHsts) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
