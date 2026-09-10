<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Feature\Query\ListFeatureFlagsQuery;
use Yume\Api\Domain\Authorization\Service\AuthorizationService;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Contracts\Bus\QueryBusInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * Lets the front end ask which features it should render.
 *
 * Ordinary callers get a flat map of key => bool. Only a caller holding
 * feature_flag.view sees the strategy, rollout percentage and payload — the
 * cohort definition of a 5% rollout is not public information.
 */
final class FeatureController
{
    public function __construct(
        private readonly QueryBusInterface $queries,
        private readonly AuthorizationService $authorization,
        private readonly RoleRepositoryInterface $roles,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function index(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $rawUserId = $request->attribute('user_id');
        $userId = is_string($rawUserId) ? UserId::fromString($rawUserId) : null;

        $flags = $this->queries->ask(new ListFeatureFlagsQuery(
            $userId?->value,
            $userId === null ? ['guest'] : $this->roles->roleSlugsForUser($userId),
            $request->ip(),
            $this->authorization->allows($userId, 'feature_flag.view'),
        ));

        return JsonResponse::ok(['features' => $flags]);
    }
}
