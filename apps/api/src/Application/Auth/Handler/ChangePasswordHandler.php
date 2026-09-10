<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\ChangePasswordCommand;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Exception\InvalidCredentialsException;
use Yume\Api\Domain\Auth\Repository\CredentialRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\Service\PasswordPolicy;
use Yume\Api\Domain\Auth\ValueObject\PasswordHash;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\Exception\UserNotFoundException;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Security\Password\PasswordHasher;

/**
 * Password change for a signed-in user.
 *
 * Requires the current password even though the caller is authenticated: a
 * borrowed session must not be enough to lock the real owner out. Other
 * sessions are revoked, the caller's own is kept so they are not signed out of
 * the page they are standing on.
 */
final class ChangePasswordHandler
{
    public function __construct(
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

    public function __invoke(ChangePasswordCommand $command): int
    {
        $userId = UserId::fromString($command->userId);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::withId($command->userId);
        }

        $credential = $this->credentials->findForUser($userId);

        if ($credential === null) {
            throw new InvalidCredentialsException();
        }

        $stored = $credential->passwordHash();

        $verified = $stored->isLegacy()
            ? $this->hasher->verifyLegacySha256($command->currentPassword, $stored->value)
            : $this->hasher->verify($command->currentPassword, $stored->value);

        if (!$verified) {
            $this->auditor->record(
                SecurityEventType::LoginFailed,
                IpAddress::fromString($command->ip),
                UserAgent::fromString($command->userAgent),
                'POST',
                '/api/v1/auth/password/change',
                $userId,
                0,
                ['event' => 'password_change_wrong_current_password'],
            );

            throw new InvalidCredentialsException('Your current password is not correct.');
        }

        $this->passwordPolicy->assertAcceptable(
            $command->newPassword,
            [$user->username()->value, $user->email()->value],
        );

        $now = $this->clock->now();
        $credential->replacePassword(PasswordHash::argon2id($this->hasher->hash($command->newPassword)), $now);

        $revoked = $this->connection->transaction(function () use ($credential, $userId, $command, $now): int {
            $this->credentials->save($credential);

            $count = 0;
            foreach ($this->sessions->findActiveForUser($userId, $now) as $session) {
                if ($session->id->value === $command->currentSessionId) {
                    continue;
                }

                $session->revoke($now, 'password_changed');
                $this->sessions->save($session);
                ++$count;
            }

            return $count;
        });

        $this->auditor->record(
            SecurityEventType::PasswordChanged,
            IpAddress::fromString($command->ip),
            UserAgent::fromString($command->userAgent),
            'POST',
            '/api/v1/auth/password/change',
            $userId,
            0,
            ['event' => 'password_changed', 'other_sessions_revoked' => $revoked],
        );

        return $revoked;
    }
}
