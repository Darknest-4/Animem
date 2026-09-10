<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Authorization\Service\AuthorizationService;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Api\Presentation\Http\Route;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * Enforces the permissions the route declared.
 *
 * This is the single choke point for "may this actor do this?". A controller
 * cannot be reached without passing through it, which is the structural
 * difference from the legacy `perm()` call: that one lived in templates, so any
 * admin action was reachable by typing its URL.
 *
 * Every denial is audited. Repeated denials from one address are themselves a
 * risk signal on the next request.
 */
final class AuthorizeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly SecurityAuditor $auditor,
    ) {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        $route = $request->attribute('route');

        if (!$route instanceof Route) {
            return $next->handle($request);
        }

        $permissions = $route->permissions();

        if ($permissions === []) {
            // Nothing further to check: the route declared public() or
            // authenticated(), and AuthenticateMiddleware already enforced the latter.
            return $next->handle($request);
        }

        $rawUserId = $request->attribute('user_id');
        $userId = is_string($rawUserId) ? UserId::fromString($rawUserId) : null;

        foreach ($permissions as $permission) {
            if ($this->authorization->allows($userId, $permission)) {
                continue;
            }

            $this->auditor->record(
                SecurityEventType::AuthorizationDenied,
                IpAddress::fromString($request->ip()),
                UserAgent::fromString($request->userAgent()),
                $request->method(),
                $request->path(),
                $userId,
                (int) ($request->attribute('risk_score') ?? 0),
                ['required_permission' => $permission, 'route' => $route->name],
            );

            // 403 for an authenticated caller, 401 for an anonymous one: the
            // anonymous caller can fix it by signing in, the authenticated one cannot.
            return $userId === null
                ? ProblemDetails::make(401, 'auth.required', 'Authentication is required for this endpoint.')
                : ProblemDetails::make(403, 'authorization.denied', 'You do not have permission to perform this action.');
        }

        return $next->handle($request);
    }
}
