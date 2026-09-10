<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Api\Presentation\Http\Router;
use Yume\Contracts\Feature\FeatureFlagsInterface;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * Matches the route and attaches it to the request.
 *
 * Also enforces route-level feature flags: a route behind a disabled flag
 * answers 404, not 403 — an endpoint that does not exist yet should not be
 * discoverable by probing for permission errors.
 */
final class RouteResolverMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Router $router,
        private readonly FeatureFlagsInterface $features,
    ) {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        $match = $this->router->match($request->method(), $request->path());

        if ($match->methodNotAllowed) {
            return ProblemDetails::make(
                405,
                'route.method_not_allowed',
                sprintf('%s is not allowed on this endpoint.', $request->method()),
            )->withHeader('Allow', implode(', ', $match->allowedMethods));
        }

        if (!$match->matched || $match->route === null) {
            return ProblemDetails::make(404, 'route.not_found', 'The requested endpoint does not exist.');
        }

        $flag = $match->route->featureFlag();
        if ($flag !== null && !$this->features->enabled($flag, ['ip' => $request->ip()])) {
            return ProblemDetails::make(404, 'route.not_found', 'The requested endpoint does not exist.');
        }

        return $next->handle(
            $request
                ->withAttribute('route', $match->route)
                ->withAttribute('route_parameters', $match->parameters),
        );
    }
}
