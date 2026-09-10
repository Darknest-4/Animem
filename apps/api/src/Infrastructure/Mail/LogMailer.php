<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Mail;

use Yume\Contracts\Logging\LoggerInterface;
use Yume\Contracts\Mail\MailerInterface;

/**
 * Default driver. Writes the message to the structured log instead of sending it.
 *
 * This is what runs until SMTP credentials are provisioned. It is deliberately
 * the default rather than a throwing stub: registration must not fail because
 * mail is not configured yet, and a developer needs to see the verification
 * link somewhere.
 *
 * The body is logged in full, which is acceptable precisely because this driver
 * is never used in production — SmtpMailer is.
 */
final class LogMailer implements MailerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $from,
    ) {
    }

    public function send(string $to, string $subject, string $body): void
    {
        $this->logger->info('Outgoing mail (log driver — nothing was sent).', [
            'from' => $this->from,
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ]);
    }
}
