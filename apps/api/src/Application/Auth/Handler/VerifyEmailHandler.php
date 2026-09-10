<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\VerifyEmailCommand;
use Yume\Api\Application\Auth\Exception\InvalidTokenException;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Repository\OneTimeTokenRepositoryInterface;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Auth\ValueObject\TokenPurpose;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Security\Token\TokenGenerator;

final class VerifyEmailHandler
{
    public function __construct(
        private readonly OneTimeTokenRepositoryInterface $tokens,
        private readonly UserRepositoryInterface $users,
        private readonly SecurityAuditor $auditor,
        private readonly ConnectionInterface $connection,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(VerifyEmailCommand $command): bool
    {
        $now = $this->clock->now();

        $token = $this->tokens->findByHash(
            TokenPurpose::EmailVerification,
            TokenHash::fromString(TokenGenerator::hash($command->token)),
        );

        if ($token === null || !$token->isUsable($now)) {
            throw new InvalidTokenException();
        }

        $user = $this->users->findById($token->userId);

        if ($user === null) {
            throw new InvalidTokenException();
        }

        $token->consume($now);
        $user->verifyEmail($now);

        $this->connection->transaction(function () use ($token, $user): void {
            $this->tokens->save($token);
            $this->users->save($user);
        });

        $this->auditor->record(
            SecurityEventType::Registered,
            IpAddress::fromString($command->ip),
            UserAgent::fromString($command->userAgent),
            'POST',
            '/api/v1/auth/email/verify',
            $user->id,
            0,
            ['event' => 'email_verified'],
        );

        return true;
    }
}
