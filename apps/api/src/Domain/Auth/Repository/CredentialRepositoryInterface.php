<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Repository;

use Yume\Api\Domain\Auth\Entity\Credential;
use Yume\Api\Domain\User\ValueObject\UserId;

interface CredentialRepositoryInterface
{
    public function findForUser(UserId $userId): ?Credential;

    public function save(Credential $credential): void;
}
