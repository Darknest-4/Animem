<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Domain\Security\RateLimit\RateLimitPolicy;
use Yume\Api\Domain\Security\RateLimit\RateLimiterInterface;
use Yume\Api\Domain\Security\Risk\RiskAction;
use Yume\Api\Presentation\Http\Request\Request;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Api\Presentation\Http\Route;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * Applies the route's rate-limit policy, tightened when the risk engine asked for it.
 *
 * Note the interaction: SecurityGateMiddleware does not itself throttle; it
 * raises a flag that this middleware turns into a smaller budget. Keeping the
 * decision and the enforcement separate means the policy stays inspectable.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterInterface $limiter,
        private readonly ClockInterface $clock,
    ) {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        $route = $request->attribute('route');
        $policy = $this->policyFor($route, $request);

        if ($policy === null) {
            return $next->handle($request);
        }

        if ($request->attribute('risk_action') === RiskAction::RateLimit->value) {
            $policy = new RateLimitPolicy(
                $policy->name . '.elevated',
                max(1, intdiv($policy->maxAttempts, 4)),
                $policy->windowSeconds,
                $policy->penaltySeconds,
            );
        }

        $key = $policy->name . '|' . $request->ip() . '|' . ($request->attribute('user_id') ?? '-');
        $result = $this->limiter->consume($policy, $key, $this->clock->now());

        if (!$result->allowed) {
            return ProblemDetails::make(
                429,
                'rate_limit.exceeded',
                'Too many requests. Please slow down.',
                ['retry_after' => $result->retryAfterSeconds],
                $result->toHeaders(),
            );
        }

        $response = $next->handle($request);

        foreach ($result->toHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function policyFor(mixed $route, RequestInterface $request): ?RateLimitPolicy
    {
        $policies = RateLimitPolicy::defaults();

        if ($route instanceof Route) {
            $named = $route->rateLimitPolicy();

            if ($named !== null) {
                return $policies[$named] ?? null;
            }
        }

        // Default budget by verb: writes are cheaper to abuse than reads.
        $isWrite = $request instanceof Request && $request->isWrite();

        return $policies[$isWrite ? 'api.write' : 'api.read'] ?? null;
    }
}
