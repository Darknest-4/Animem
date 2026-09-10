<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\DTO\SessionView;
use Yume\Api\Application\Auth\Query\GetSessionsQuery;
use Yume\Api\Domain\Auth\Entity\Session;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;

final class GetSessionsHandler
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @return list<SessionView> */
    public function __invoke(GetSessionsQuery $query): array
    {
        $sessions = $this->sessions->findActiveForUser(
            UserId::fromString($query->userId),
            $this->clock->now(),
        );

        return array_map(
            static fn (Session $session): SessionView => SessionView::fromEntity($session, $query->currentSessionId),
            $sessions,
        );
    }
}
