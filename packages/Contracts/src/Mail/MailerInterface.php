<?php

declare(strict_types=1);

namespace Yume\Contracts\Mail;

interface MailerInterface
{
    public function send(string $to, string $subject, string $body): void;
}
