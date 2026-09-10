<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\ResetPasswordCommand;
use Yume\Api\Application\Auth\Exception\InvalidTokenException;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Repository\CredentialRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\OneTimeTokenRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\Service\PasswordPolicy;
use Yume\Api\Domain\Auth\ValueObject\PasswordHash;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Auth\ValueObject\TokenPurpose;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Security\Password\PasswordHasher;
use Yume\Security\Token\TokenGenerator;

/**
 * Consumes a reset token and sets a new password.
 *
 * Every existing session is revoked in the same transaction. If the reset was
 * triggered because the account was compromised, leaving the attacker's session
 * alive would defeat the entire exercise.
 */
final class ResetPasswordHandler
{
    public function __construct(
        private readonly OneTimeTokenRepositoryInterface $tokens,
        private readonly UserRepositoryInterface $users,
        private readonly CredentialRepositoryInterface $credentials,
        private readonly SessionRepositoryInterface $sessions,
        private readonly PasswordPolicy $passwordPolicy,
        private readonly PasswordHasher $hasher,
        private readonly SecurityAuditor $auditor,
        private readonly ConnectionInterface $connection,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ResetPasswordCommand $command): bool
    {
        $now = $this->clock->now();

        $token = $this->tokens->findByHash(
            TokenPurpose::PasswordReset,
            TokenHash::fromString(TokenGenerator::hash($command->token)),
        );

        if ($token === null || !$token->isUsable($now)) {
            throw new InvalidTokenException();
        }

        $user = $this->users->findById($token->userId);

        if ($user === null) {
            throw new InvalidTokenException();
        }

        $this->passwordPolicy->assertAcceptable(
            $command->newPassword,
            [$user->username()->value, $user->email()->value],
        );

        $credential = $this->credentials->findForUser($user->id);

        if ($credential === null) {
            throw new InvalidTokenException();
        }

        $token->consume($now);
        $credential->replacePassword(
            PasswordHash::argon2id($this->hasher->hash($command->newPassword)),
            $now,
        );

        // A verified reset also proves control of the mailbox.
        $user->verifyEmail($now);

        $revoked = $this->connection->transaction(function () use ($token, $credential, $user, $now): int {
            $this->tokens->save($token);
            $this->credentials->save($credential);
            $this->users->save($user);

            return $this->sessions->revokeAllForUser($user->id, $now, 'password_reset');
        });

        $this->auditor->record(
            SecurityEventType::PasswordChanged,
            IpAddress::fromString($command->ip),
            UserAgent::fromString($command->userAgent),
            'POST',
            '/api/v1/auth/password/reset',
            $user->id,
            0,
            ['event' => 'password_reset_completed', 'sessions_revoked' => $revoked],
        );

        return true;
    }
}
