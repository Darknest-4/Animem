<?php

declare(strict_types=1);

namespace Yume\Shared\Logging;

use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Logging\LoggerInterface;
use Yume\Shared\Support\Json;

/**
 * One JSON object per line, written to a stream.
 *
 * In containers the stream is php://stderr so the platform collects logs; nothing
 * is ever echoed into an HTTP response. The legacy site printed failing SQL
 * straight to the browser, which handed attackers the schema.
 */
final class JsonLogger implements LoggerInterface
{
    private const LEVELS = [
        'debug' => 100,
        'info' => 200,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
    ];

    /** @var resource */
    private $stream;

    /** @param array<string, mixed> $context */
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly string $streamTarget = 'php://stderr',
        private readonly string $minimumLevel = 'debug',
        private readonly array $context = [],
        private readonly string $channel = 'app',
    ) {
        $handle = fopen($streamTarget, 'ab');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Unable to open log stream "%s".', $streamTarget));
        }

        $this->stream = $handle;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function withContext(array $context): LoggerInterface
    {
        return new self(
            $this->clock,
            $this->streamTarget,
            $this->minimumLevel,
            array_merge($this->context, $context),
            $this->channel,
        );
    }

    /** @param array<string, mixed> $context */
    private function log(string $level, string $message, array $context): void
    {
        if (self::LEVELS[$level] < (self::LEVELS[$this->minimumLevel] ?? 100)) {
            return;
        }

        $record = [
            'timestamp' => $this->clock->now()->format(\DateTimeInterface::RFC3339_EXTENDED),
            'level' => $level,
            'channel' => $this->channel,
            'message' => $message,
            'context' => self::redact(array_merge($this->context, $context)),
        ];

        fwrite($this->stream, Json::encode($record) . "\n");
    }

    /**
     * Strips anything that must never reach a log aggregator.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'secret', 'authorization', 'cookie', 'session_token', 'api_key'];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $context[$key] = '[redacted]';
                continue;
            }
            if (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }

        return $context;
    }
}
