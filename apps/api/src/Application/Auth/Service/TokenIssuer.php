<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Service;

use Yume\Api\Domain\Auth\Entity\OneTimeToken;
use Yume\Api\Domain\Auth\Repository\OneTimeTokenRepositoryInterface;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Auth\ValueObject\TokenPurpose;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;
use Yume\Contracts\Logging\LoggerInterface;
use Yume\Contracts\Mail\MailerInterface;
use Yume\Security\Token\TokenGenerator;

/**
 * Issues a one-time token, invalidates its predecessors, and mails the link.
 *
 * Shared by verification and password reset because the security properties are
 * identical and must not drift apart: issuing a new token kills the old ones,
 * only the digest is stored, and a mail failure never propagates to the caller.
 */
final class TokenIssuer
{
    public function __construct(
        private readonly OneTimeTokenRepositoryInterface $tokens,
        private readonly TokenGenerator $generator,
        private readonly MailerInterface $mailer,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly string $baseUrl,
    ) {
    }

    public function issueAndSend(
        UserId $userId,
        string $emailAddress,
        string $username,
        TokenPurpose $purpose,
        ?IpAddress $requestedIp = null,
    ): void {
        $now = $this->clock->now();

        // Requesting a second link makes the first one dead, so two valid links
        // never sit in two places at once.
        $this->tokens->consumeAllForUser($userId, $purpose, $now);

        $token = $this->generator->generate();

        $this->tokens->save(OneTimeToken::issue(
            $this->ids->generate(),
            $userId,
            $purpose,
            TokenHash::fromString($token->hash),
            $now,
            $requestedIp,
        ));

        try {
            $this->mailer->send(
                $emailAddress,
                $this->subjectFor($purpose),
                $this->bodyFor($purpose, $username, $token->plain),
            );
        } catch (\Throwable $e) {
            // The token is already valid; a transient SMTP failure must not roll
            // that back or fail the user's request. It is logged for follow-up.
            $this->logger->error('Failed to send an authentication email.', [
                'purpose' => $purpose->value,
                'user_id' => $userId->value,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function subjectFor(TokenPurpose $purpose): string
    {
        return match ($purpose) {
            TokenPurpose::EmailVerification => 'Erősítsd meg az e-mail címed',
            TokenPurpose::PasswordReset => 'Jelszó visszaállítása',
        };
    }

    private function bodyFor(TokenPurpose $purpose, string $username, string $plainToken): string
    {
        $link = rtrim($this->baseUrl, '/') . match ($purpose) {
            TokenPurpose::EmailVerification => '/auth/verify-email?token=',
            TokenPurpose::PasswordReset => '/auth/reset-password?token=',
        } . $plainToken;

        $hours = intdiv($purpose->lifetimeSeconds(), 3600);

        return match ($purpose) {
            TokenPurpose::EmailVerification => <<<TEXT
                Szia {$username}!

                Erősítsd meg az e-mail címed az alábbi linken:

                {$link}

                A link {$hours} óráig érvényes. Ha nem te regisztráltál, hagyd figyelmen kívül ezt a levelet.
                TEXT,
            TokenPurpose::PasswordReset => <<<TEXT
                Szia {$username}!

                Valaki jelszó-visszaállítást kért ehhez a fiókhoz. Ha te voltál, állítsd be az új jelszavad itt:

                {$link}

                A link {$hours} óráig érvényes, és csak egyszer használható.

                Ha nem te kérted, nem kell tenned semmit — a jelszavad változatlan marad.
                TEXT,
        };
    }
}
