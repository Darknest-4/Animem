<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Repository;

use Yume\Api\Domain\Auth\Entity\OneTimeToken;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Auth\ValueObject\TokenPurpose;
use Yume\Api\Domain\User\ValueObject\UserId;

interface OneTimeTokenRepositoryInterface
{
    public function findByHash(TokenPurpose $purpose, TokenHash $hash): ?OneTimeToken;

    public function save(OneTimeToken $token): void;

    /**
     * Invalidates every outstanding token of this purpose for the user.
     *
     * Called before issuing a new one, so requesting a second reset link makes
     * the first one dead rather than leaving two valid links in two inboxes.
     */
    public function consumeAllForUser(UserId $userId, TokenPurpose $purpose, \DateTimeImmutable $now): int;

    public function deleteExpiredBefore(TokenPurpose $purpose, \DateTimeImmutable $cutoff): int;
}
