<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\LoginCommand;
use Yume\Api\Application\Auth\DTO\AuthenticatedUser;
use Yume\Api\Application\Auth\DTO\LoginResult;
use Yume\Api\Application\Auth\Exception\TooManyAttemptsException;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\Entity\Session;
use Yume\Api\Domain\Auth\Exception\AccountLockedException;
use Yume\Api\Domain\Auth\Exception\AccountNotActiveException;
use Yume\Api\Domain\Auth\Exception\InvalidCredentialsException;
use Yume\Api\Domain\Auth\Repository\CredentialRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\Service\SessionPolicy;
use Yume\Api\Domain\Auth\ValueObject\PasswordHash;
use Yume\Api\Domain\Auth\ValueObject\SessionId;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Security\RateLimit\RateLimitPolicy;
use Yume\Api\Domain\Security\RateLimit\RateLimiterInterface;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;
use Yume\Security\Password\PasswordHasher;
use Yume\Security\Token\TokenGenerator;

/**
 * Password login.
 *
 * Behaviour worth calling out:
 *  - The same generic error is returned whether the account does not exist or
 *    the password is wrong, and a dummy hash is verified in the "no such user"
 *    branch so the two paths cost the same time. Otherwise the endpoint becomes
 *    a username oracle.
 *  - A legacy unsalted SHA-256 hash still authenticates once, and is immediately
 *    replaced with Argon2id. That is the whole migration: no mass password reset.
 *  - The plaintext session token leaves this handler once. Only its digest is
 *    persisted.
 */
final class LoginHandler
{
    /**
     * A real Argon2id hash with the production parameters, of a passphrase nobody
     * holds. Verified in the unknown-user branch so that "no such account" costs
     * the same wall-clock time as "wrong password"; a syntactically invalid hash
     * would return instantly and reintroduce the timing oracle.
     */
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=2$QW9VY1pvYVdGVEJpR3kySg$yqe2KICld0hG4c3fl66CFKL8F4p7s47tbOyuq7CQ8Do';

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CredentialRepositoryInterface $credentials,
        private readonly SessionRepositoryInterface $sessions,
        private readonly RoleRepositoryInterface $roles,
        private readonly PasswordHasher $hasher,
        private readonly SessionPolicy $sessionPolicy,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly SecurityAuditor $auditor,
        private readonly TokenGenerator $tokens,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
        private readonly int $maxFailedAttempts = 8,
        private readonly int $lockSeconds = 900,
    ) {
    }

    public function __invoke(LoginCommand $command): LoginResult
    {
        $ip = IpAddress::fromString($command->ip);
        $userAgent = UserAgent::fromString($command->userAgent);
        $now = $this->clock->now();

        $policy = RateLimitPolicy::defaults()['auth.login'];
        $rateKey = 'login:' . $ip->value . ':' . mb_strtolower(trim($command->identifier));

        $limit = $this->rateLimiter->consume($policy, $rateKey, $now);
        if (!$limit->allowed) {
            $this->auditor->record(
                SecurityEventType::LoginBlocked,
                $ip,
                $userAgent,
                'POST',
                '/api/v1/auth/login',
                null,
                0,
                ['reason' => 'rate_limited', 'identifier' => $this->maskIdentifier($command->identifier)],
            );

            throw new TooManyAttemptsException($limit->retryAfterSeconds);
        }

        $user = $this->users->findByIdentifier($command->identifier);

        if ($user === null) {
            // Constant-time-ish: spend the same Argon2id verification as a real miss.
            $this->hasher->verify($command->password, self::DUMMY_HASH);
            $this->recordFailure($ip, $userAgent, $command->identifier, null, 'unknown_identifier');

            throw new InvalidCredentialsException();
        }

        $credential = $this->credentials->findForUser($user->id);
        if ($credential === null) {
            $this->hasher->verify($command->password, self::DUMMY_HASH);
            $this->recordFailure($ip, $userAgent, $command->identifier, $user->id->value, 'no_credential');

            throw new InvalidCredentialsException();
        }

        if ($credential->isLocked($now)) {
            $this->auditor->record(
                SecurityEventType::LoginBlocked,
                $ip,
                $userAgent,
                'POST',
                '/api/v1/auth/login',
                $user->id,
                0,
                ['reason' => 'account_locked'],
            );

            throw new AccountLockedException();
        }

        $storedHash = $credential->passwordHash();

        $verified = $storedHash->isLegacy()
            ? $this->hasher->verifyLegacySha256($command->password, $storedHash->value)
            : $this->hasher->verify($command->password, $storedHash->value);

        if (!$verified) {
            $credential->recordFailure($now, $this->maxFailedAttempts, $this->lockSeconds);
            $this->credentials->save($credential);
            $this->recordFailure($ip, $userAgent, $command->identifier, $user->id->value, 'bad_password');

            throw new InvalidCredentialsException();
        }

        if (!$user->canAuthenticate()) {
            $this->auditor->record(
                SecurityEventType::LoginBlocked,
                $ip,
                $userAgent,
                'POST',
                '/api/v1/auth/login',
                $user->id,
                0,
                ['reason' => 'status_' . $user->status()->value],
            );

            throw new AccountNotActiveException();
        }

        $this->upgradeHashIfNeeded($credential, $command->password, $ip, $userAgent, $user->id);

        $credential->recordSuccess();
        $this->credentials->save($credential);
        $this->rateLimiter->reset($policy, $rateKey);

        $token = $this->tokens->generate();
        $session = Session::start(
            SessionId::fromString($this->ids->generate()),
            $user->id,
            TokenHash::fromString($token->hash),
            $ip,
            $userAgent,
            $now,
            $this->sessionPolicy->absoluteLifetimeSeconds,
        );

        $this->enforceConcurrentSessionCap($user->id, $now);
        $this->sessions->save($session);

        $this->auditor->record(
            SecurityEventType::LoginSucceeded,
            $ip,
            $userAgent,
            'POST',
            '/api/v1/auth/login',
            $user->id,
            0,
            ['session_id' => $session->id->value],
        );

        return new LoginResult(
            AuthenticatedUser::fromEntity($user, $this->roles->permissionsForUser($user->id)->toStrings()),
            $token->plain,
            $session->id->value,
            $session->expiresAt(),
        );
    }

    private function upgradeHashIfNeeded(
        \Yume\Api\Domain\Auth\Entity\Credential $credential,
        string $plainPassword,
        IpAddress $ip,
        UserAgent $userAgent,
        \Yume\Api\Domain\User\ValueObject\UserId $userId,
    ): void {
        $hash = $credential->passwordHash();

        if (!$hash->isLegacy() && !$this->hasher->needsRehash($hash->value)) {
            return;
        }

        $credential->upgradeHash(PasswordHash::argon2id($this->hasher->hash($plainPassword)));

        $this->auditor->record(
            SecurityEventType::PasswordUpgraded,
            $ip,
            $userAgent,
            'POST',
            '/api/v1/auth/login',
            $userId,
            0,
            ['from' => $hash->algorithm->value, 'to' => 'argon2id'],
        );
    }

    private function enforceConcurrentSessionCap(
        \Yume\Api\Domain\User\ValueObject\UserId $userId,
        \DateTimeImmutable $now,
    ): void {
        $active = $this->sessions->findActiveForUser($userId, $now);

        if (count($active) < $this->sessionPolicy->maxConcurrentSessions) {
            return;
        }

        // findActiveForUser returns newest first, so the tail is the oldest.
        foreach (array_slice($active, $this->sessionPolicy->maxConcurrentSessions - 1) as $stale) {
            $stale->revoke($now, 'concurrent_session_limit');
            $this->sessions->save($stale);
        }
    }

    private function recordFailure(
        IpAddress $ip,
        UserAgent $userAgent,
        string $identifier,
        ?string $userId,
        string $reason,
    ): void {
        $this->auditor->record(
            SecurityEventType::LoginFailed,
            $ip,
            $userAgent,
            'POST',
            '/api/v1/auth/login',
            $userId === null ? null : \Yume\Api\Domain\User\ValueObject\UserId::fromString($userId),
            0,
            ['reason' => $reason, 'identifier' => $this->maskIdentifier($identifier)],
        );
    }

    /** Enough to correlate attacks, not enough to leak an address into the log store. */
    private function maskIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        $length = mb_strlen($identifier);

        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        return mb_substr($identifier, 0, 2) . str_repeat('*', min(8, $length - 2));
    }
}
