<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Handler;

use Yume\Api\Application\Auth\Command\ResendVerificationCommand;
use Yume\Api\Application\Auth\Exception\TooManyAttemptsException;
use Yume\Api\Application\Auth\Service\TokenIssuer;
use Yume\Api\Domain\Auth\ValueObject\TokenPurpose;
use Yume\Api\Domain\Security\RateLimit\RateLimitPolicy;
use Yume\Api\Domain\Security\RateLimit\RateLimiterInterface;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Domain\User\ValueObject\Email;
use Yume\Contracts\Clock\ClockInterface;

/**
 * Always reports success.
 *
 * Reporting "no such account" would turn this endpoint into a free membership
 * oracle: anyone could test an email address against the user base.
 */
final class ResendVerificationHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenIssuer $issuer,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ResendVerificationCommand $command): bool
    {
        $ip = IpAddress::fromString($command->ip);
        $policy = RateLimitPolicy::defaults()['auth.email_dispatch'];

        $limit = $this->rateLimiter->consume($policy, 'verify-resend:' . $ip->subnetKey(), $this->clock->now());
        if (!$limit->allowed) {
            throw new TooManyAttemptsException($limit->retryAfterSeconds);
        }

        try {
            $email = Email::fromString($command->email);
        } catch (\InvalidArgumentException) {
            return true;
        }

        $user = $this->users->findByEmail($email);

        if ($user !== null && !$user->isEmailVerified()) {
            $this->issuer->issueAndSend(
                $user->id,
                $user->email()->value,
                $user->username()->value,
                TokenPurpose::EmailVerification,
                $ip,
            );
        }

        return true;
    }
}
