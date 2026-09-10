<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Mail;

use Yume\Contracts\Logging\LoggerInterface;
use Yume\Contracts\Mail\MailerInterface;

/**
 * Minimal SMTP client.
 *
 * Written directly rather than pulling in a mail library because the entire
 * requirement is "send a plain-text message to one recipient over an
 * authenticated or local SMTP connection". Mailpit covers development; a relay
 * covers production.
 *
 * Header injection is the classic bug here: a newline in the recipient or
 * subject lets an attacker append their own headers and turn the endpoint into
 * an open relay. Both are stripped, not escaped.
 */
final class SmtpMailer implements MailerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $host,
        private readonly int $port,
        private readonly string $from,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly int $timeoutSeconds = 10,
    ) {
    }

    public function send(string $to, string $subject, string $body): void
    {
        $to = self::sanitiseHeaderValue($to);
        $subject = self::sanitiseHeaderValue($subject);

        $socket = @fsockopen($this->host, $this->port, $errorCode, $errorMessage, $this->timeoutSeconds);

        if ($socket === false) {
            // Mail delivery must never take down the request that triggered it.
            // The caller decides whether the user needs to know.
            throw new \RuntimeException(sprintf('SMTP connection to %s:%d failed: %s', $this->host, $this->port, $errorMessage));
        }

        stream_set_timeout($socket, $this->timeoutSeconds);

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO yume', 250);

            if ($this->username !== null && $this->password !== null) {
                $this->command($socket, 'AUTH LOGIN', 334);
                $this->command($socket, base64_encode($this->username), 334);
                $this->command($socket, base64_encode($this->password), 235);
            }

            $this->command($socket, sprintf('MAIL FROM:<%s>', $this->from), 250);
            $this->command($socket, sprintf('RCPT TO:<%s>', $to), 250);
            $this->command($socket, 'DATA', 354);

            $message = implode("\r\n", [
                'From: ' . $this->from,
                'To: ' . $to,
                'Subject: ' . $subject,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'Date: ' . date(\DATE_RFC2822),
                '',
                // A lone "." on its own line terminates DATA, so it must be doubled.
                preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $body)),
                '.',
            ]);

            $this->command($socket, $message, 250);
            $this->command($socket, 'QUIT', 221);

            $this->logger->info('Mail sent.', ['to' => $to, 'subject' => $subject]);
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function command(mixed $socket, string $command, int $expectedCode): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expectedCode);
    }

    /** @param resource $socket */
    private function expect(mixed $socket, int $expectedCode): void
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            // A multi-line reply keeps a hyphen in the fourth column.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $code = (int) substr(trim($response), 0, 3);

        if ($code !== $expectedCode) {
            throw new \RuntimeException(sprintf('SMTP expected %d, got: %s', $expectedCode, trim($response)));
        }
    }

    /** Strips CR/LF so a crafted address or subject cannot inject headers. */
    private static function sanitiseHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }
}
