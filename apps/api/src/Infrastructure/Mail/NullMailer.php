<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Mail;

use Yume\Contracts\Mail\MailerInterface;

/** Discards everything. For tests that assert on behaviour other than mail. */
final class NullMailer implements MailerInterface
{
    public function send(string $to, string $subject, string $body): void
    {
    }
}
