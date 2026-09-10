<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\RequestPasswordResetCommand;
use Yume\Api\Application\Auth\Exception\TooManyAttemptsException;
use Yume\Api\Application\Auth\Service\TokenIssuer;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Auth\ValueObject\TokenPurpose;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Security\RateLimit\RateLimitPolicy;
use Yume\Api\Domain\Security\RateLimit\RateLimiterInterface;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\Email;
use Yume\Contracts\Clock\ClockInterface;

/**
 * Always reports success, whether or not the address belongs to an account.
 *
 * The audit trail records which of the two happened, so an operator can see a
 * probing run; the caller cannot.
 */
final class RequestPasswordResetHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenIssuer $issuer,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly SecurityAuditor $auditor,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(RequestPasswordResetCommand $command): bool
    {
        $ip = IpAddress::fromString($command->ip);
        $userAgent = UserAgent::fromString($command->userAgent);
        $policy = RateLimitPolicy::defaults()['auth.email_dispatch'];

        $limit = $this->rateLimiter->consume($policy, 'reset:' . $ip->subnetKey(), $this->clock->now());
        if (!$limit->allowed) {
            throw new TooManyAttemptsException($limit->retryAfterSeconds);
        }

        try {
            $email = Email::fromString($command->email);
        } catch (\InvalidArgumentException) {
            return true;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            $this->auditor->record(
                SecurityEventType::LoginFailed,
                $ip,
                $userAgent,
                'POST',
                '/api/v1/auth/password/forgot',
                null,
                0,
                ['event' => 'password_reset_requested_for_unknown_address'],
            );

            return true;
        }

        $this->issuer->issueAndSend(
            $user->id,
            $user->email()->value,
            $user->username()->value,
            TokenPurpose::PasswordReset,
            $ip,
        );

        $this->auditor->record(
            SecurityEventType::PasswordChanged,
            $ip,
            $userAgent,
            'POST',
            '/api/v1/auth/password/forgot',
            $user->id,
            0,
            ['event' => 'password_reset_requested'],
        );

        return true;
    }
}
