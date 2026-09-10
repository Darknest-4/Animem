<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Auth\Command\RevokeSessionCommand;
use Yume\Api\Application\Auth\DTO\SessionView;
use Yume\Api\Application\Auth\Query\GetSessionsQuery;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Bus\CommandBusInterface;
use Yume\Contracts\Bus\QueryBusInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/** "Where am I signed in?" and "sign me out everywhere". */
final class SessionController
{
    public function __construct(
        private readonly CommandBusInterface $commands,
        private readonly QueryBusInterface $queries,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function index(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $userId = $request->attribute('user_id');

        if (!is_string($userId)) {
            return ProblemDetails::make(401, 'auth.required', 'Authentication is required for this endpoint.');
        }

        $sessionId = $request->attribute('session_id');

        /** @var list<SessionView> $sessions */
        $sessions = $this->queries->ask(new GetSessionsQuery(
            $userId,
            is_string($sessionId) ? $sessionId : null,
        ));

        return JsonResponse::ok([
            'sessions' => array_map(static fn (SessionView $s): array => $s->toArray(), $sessions),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function revoke(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $userId = $request->attribute('user_id');

        if (!is_string($userId)) {
            return ProblemDetails::make(401, 'auth.required', 'Authentication is required for this endpoint.');
        }

        $revoked = $this->commands->dispatch(new RevokeSessionCommand(
            $userId,
            $parameters['id'] ?? '',
            $request->ip(),
            $request->userAgent(),
        ));

        return JsonResponse::ok(['revoked' => $revoked]);
    }

    /** @param array<string, string> $parameters */
    public function revokeAll(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $userId = $request->attribute('user_id');

        if (!is_string($userId)) {
            return ProblemDetails::make(401, 'auth.required', 'Authentication is required for this endpoint.');
        }

        $revoked = $this->commands->dispatch(new RevokeSessionCommand(
            $userId,
            '',
            $request->ip(),
            $request->userAgent(),
            allSessions: true,
        ));

        return JsonResponse::ok(['revoked' => $revoked]);
    }
}
