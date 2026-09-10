<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http;

use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Shared\Container\Container;

/**
 * Terminal handler: resolves the matched controller and invokes it.
 *
 * By the time this runs, RouteResolverMiddleware has already put the matched
 * route on the request and AuthorizeMiddleware has already enforced it. A
 * controller therefore never needs — and never gets — a permission check of its own.
 */
final class ControllerRunner implements HandlerInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
    ) {
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        $route = $request->attribute('route');

        if (!$route instanceof Route) {
            // Defensive: RouteResolverMiddleware always sets this, and returns a
            // 404/405 itself when it cannot.
            return ProblemDetails::make(404, 'route.not_found', 'The requested endpoint does not exist.');
        }

        [$class, $method] = $route->handler;
        $controller = $this->container->get($class);

        if (!method_exists($controller, $method)) {
            throw new \LogicException(sprintf('Controller %s has no method %s().', $class, $method));
        }

        /** @var array<string, string> $parameters */
        $parameters = $request->attribute('route_parameters', []);

        return $controller->{$method}($request, $parameters);
    }
}
