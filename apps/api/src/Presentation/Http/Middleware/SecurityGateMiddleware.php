<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Security\Audit\SecurityAuditRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Security\Risk\RiskAction;
use Yume\Api\Domain\Security\Risk\RiskContext;
use Yume\Api\Domain\Security\Risk\RiskEngine;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Presentation\Http\Request\Request;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * Runs the risk engine and applies its decision.
 *
 *   Allow      → continue silently
 *   Monitor    → continue, but audit
 *   RateLimit  → continue; RateLimitMiddleware applies a tightened budget
 *   Challenge  → 428, client must complete a challenge
 *   Restrict   → continue for reads, refuse writes
 *   Block      → 403
 *
 * Placed before authentication on purpose: a banned address should not get to
 * spend Argon2id cycles on a login attempt.
 */
final class SecurityGateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RiskEngine $engine,
        private readonly SecurityAuditRepositoryInterface $audit,
        private readonly SecurityAuditor $auditor,
        private readonly ClockInterface $clock,
    ) {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        $now = $this->clock->now();
        $ip = IpAddress::fromString($request->ip());
        $userAgent = UserAgent::fromString($request->userAgent());

        $context = new RiskContext(
            $ip,
            $userAgent,
            $request->method(),
            $request->path(),
            $request instanceof Request ? $request->headers() : [],
        );

        if ($context->isSensitivePath()) {
            // The counter costs a query, so it is only fetched where it changes a
            // decision: login, register, password reset and admin paths.
            $context = $context->withCounters(
                $this->audit->countRecentFailedLogins($ip, $now->modify('-15 minutes')),
                0,
            );
        }

        $decision = $this->engine->evaluate($context, $now);

        if ($decision->action === RiskAction::Block) {
            $this->auditor->record(
                SecurityEventType::RiskBlocked,
                $ip,
                $userAgent,
                $request->method(),
                $request->path(),
                null,
                $decision->score->value,
                ['signals' => $decision->score->signalNames(), 'reason' => $decision->reason],
            );

            return ProblemDetails::make(403, 'security.blocked', 'This request was blocked.');
        }

        if ($decision->action === RiskAction::Challenge) {
            $this->auditor->record(
                SecurityEventType::RiskChallenged,
                $ip,
                $userAgent,
                $request->method(),
                $request->path(),
                null,
                $decision->score->value,
                ['signals' => $decision->score->signalNames()],
            );

            return ProblemDetails::make(
                428,
                'security.challenge_required',
                'Additional verification is required before this request can proceed.',
            );
        }

        if ($decision->action === RiskAction::Restrict && $request instanceof Request && $request->isWrite()) {
            return ProblemDetails::make(
                403,
                'security.read_only',
                'Write access is temporarily restricted for this client.',
            );
        }

        if ($decision->shouldAudit()) {
            $this->auditor->record(
                SecurityEventType::RateLimited,
                $ip,
                $userAgent,
                $request->method(),
                $request->path(),
                null,
                $decision->score->value,
                ['action' => $decision->action->value, 'signals' => $decision->score->signalNames()],
            );
        }

        return $next->handle(
            $request
                ->withAttribute('risk_score', $decision->score->value)
                ->withAttribute('risk_action', $decision->action->value),
        );
    }
}
