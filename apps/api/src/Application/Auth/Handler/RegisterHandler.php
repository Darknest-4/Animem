<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\RegisterCommand;
use Yume\Api\Application\Auth\DTO\AuthenticatedUser;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Entity\Credential;
use Yume\Api\Domain\Auth\Repository\CredentialRepositoryInterface;
use Yume\Api\Domain\Auth\Service\PasswordPolicy;
use Yume\Api\Domain\Auth\ValueObject\PasswordHash;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Security\RateLimit\RateLimitPolicy;
use Yume\Api\Domain\Security\RateLimit\RateLimiterInterface;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\Entity\User;
use Yume\Api\Domain\User\Exception\EmailAlreadyUsedException;
use Yume\Api\Domain\User\Exception\UsernameAlreadyUsedException;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\Email;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Domain\User\ValueObject\Username;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Security\Password\PasswordHasher;

/**
 * Registration — a use case the legacy site never had at all.
 *
 * The user row, the credential row and the default role assignment are written
 * in one transaction; a half-registered account that can never log in is worse
 * than a failed registration.
 */
final class RegisterHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CredentialRepositoryInterface $credentials,
        private readonly RoleRepositoryInterface $roles,
        private readonly PasswordPolicy $passwordPolicy,
        private readonly PasswordHasher $hasher,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly SecurityAuditor $auditor,
        private readonly ConnectionInterface $connection,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
        private readonly bool $requireEmailVerification = true,
        private readonly string $defaultRole = 'user',
    ) {
    }

    public function __invoke(RegisterCommand $command): AuthenticatedUser
    {
        $ip = IpAddress::fromString($command->ip);
        $userAgent = UserAgent::fromString($command->userAgent);
        $now = $this->clock->now();

        $policy = RateLimitPolicy::defaults()['auth.register'];
        $limit = $this->rateLimiter->consume($policy, 'register:' . $ip->subnetKey(), $now);
        if (!$limit->allowed) {
            throw new \Yume\Api\Application\Auth\Exception\TooManyAttemptsException($limit->retryAfterSeconds);
        }

        if (!$command->acceptedTerms) {
            throw new \Yume\Api\Application\Auth\Exception\TermsNotAcceptedException();
        }

        $username = Username::fromString($command->username);
        $email = Email::fromString($command->email);

        // Checked up front for a good error message; the unique indexes in
        // PostgreSQL are what actually guarantee it under concurrency.
        $this->passwordPolicy->assertAcceptable($command->password, [$username->value, $email->value]);

        if ($this->users->usernameExists($username)) {
            throw new UsernameAlreadyUsedException();
        }

        if ($this->users->emailExists($email)) {
            throw new EmailAlreadyUsedException();
        }

        $userId = UserId::fromString($this->ids->generate());
        $user = User::register($userId, $username, $email, $now, [$this->defaultRole], $this->requireEmailVerification);
        $credential = Credential::create(
            $userId,
            PasswordHash::argon2id($this->hasher->hash($command->password)),
            $now,
        );

        $this->connection->transaction(function () use ($user, $credential, $userId): void {
            $this->users->save($user);
            $this->credentials->save($credential);
            $this->roles->assignRole($userId, $this->defaultRole);
        });

        $this->auditor->record(
            SecurityEventType::Registered,
            $ip,
            $userAgent,
            'POST',
            '/api/v1/auth/register',
            $userId,
            0,
            ['username' => $username->value, 'requires_verification' => $this->requireEmailVerification],
        );

        return AuthenticatedUser::fromEntity(
            $user,
            $this->roles->permissionsForUser($userId)->toStrings(),
        );
    }
}
