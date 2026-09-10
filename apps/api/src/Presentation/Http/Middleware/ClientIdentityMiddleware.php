<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Infrastructure\Security\TrustedProxyResolver;
use Yume\Api\Presentation\Http\Request\Request;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;

/**
 * Establishes who the request claims to come from, before anything acts on it.
 *
 * Runs immediately after the error handler so that every later middleware — bans,
 * risk scoring, rate limiting, audit — reads the same, proxy-validated client IP.
 */
final class ClientIdentityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TrustedProxyResolver $proxyResolver,
        private readonly IdGeneratorInterface $ids,
    ) {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        $headers = $request instanceof Request ? $request->headers() : [];

        $clientIp = $this->proxyResolver->resolve(
            $request->ip(),
            $headers,
        );

        $requestId = $request->header('x-request-id') ?? $this->ids->generate();

        $response = $next->handle(
            $request
                ->withAttribute('client_ip', $clientIp->value)
                ->withAttribute('request_id', $requestId),
        );

        // Echoed back so a client-side error report can be correlated with the log line.
        return $response->withHeader('X-Request-Id', $requestId);
    }
}
