<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Repository;

use Yume\Api\Domain\Auth\Entity\Session;
use Yume\Api\Domain\Auth\ValueObject\SessionId;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\User\ValueObject\UserId;

interface SessionRepositoryInterface
{
    public function findByTokenHash(TokenHash $hash): ?Session;

    public function findById(SessionId $id): ?Session;

    /** @return list<Session> active sessions, newest first */
    public function findActiveForUser(UserId $userId, \DateTimeImmutable $now): array;

    public function save(Session $session): void;

    public function revokeAllForUser(UserId $userId, \DateTimeImmutable $now, string $reason): int;

    /** Housekeeping for the worker: drops rows that expired long ago. */
    public function deleteExpiredBefore(\DateTimeImmutable $cutoff): int;
}
