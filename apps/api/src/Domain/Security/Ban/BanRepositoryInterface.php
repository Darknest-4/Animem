<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Ban;

use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\User\ValueObject\UserId;

interface BanRepositoryInterface
{
    /** Checks IP, subnet, user and global scopes in one lookup. */
    public function findActiveFor(IpAddress $ip, ?UserId $userId, \DateTimeImmutable $now): ?Ban;

    public function save(Ban $ban): void;

    public function lift(string $banId, \DateTimeImmutable $now): void;

    /** @return list<Ban> */
    public function listActive(\DateTimeImmutable $now, int $limit = 100): array;
}
