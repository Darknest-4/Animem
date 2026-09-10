<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http;

use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Shared\Container\Container;

/**
 * Middleware pipeline terminating in the matched controller.
 *
 * Order is fixed here rather than per route, because the security-relevant
 * ordering (identify the client, then check bans and risk, then rate limit, then
 * authenticate, then authorise) must not be reorderable by whoever adds a route.
 */
final class Kernel implements HandlerInterface
{
    /** @param list<class-string<MiddlewareInterface>> $middleware */
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly array $middleware,
    ) {
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->pipeline(0)->handle($request);
    }

    private function pipeline(int $index): HandlerInterface
    {
        if (!isset($this->middleware[$index])) {
            return new ControllerRunner($this->container, $this->router);
        }

        $middlewareClass = $this->middleware[$index];

        return new class($this->container, $middlewareClass, $this->pipeline($index + 1)) implements HandlerInterface {
            /** @param class-string<MiddlewareInterface> $middlewareClass */
            public function __construct(
                private readonly Container $container,
                private readonly string $middlewareClass,
                private readonly HandlerInterface $next,
            ) {
            }

            public function handle(RequestInterface $request): ResponseInterface
            {
                $middleware = $this->container->get($this->middlewareClass);

                if (!$middleware instanceof MiddlewareInterface) {
                    throw new \LogicException(sprintf('%s is not a middleware.', $this->middlewareClass));
                }

                return $middleware->process($request, $this->next);
            }
        };
    }
}
