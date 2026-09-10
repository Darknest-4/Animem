<?php

declare(strict_types=1);

namespace Yume\Shared\Identity;

use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;

/**
 * UUIDv7 (RFC 9562): 48-bit millisecond timestamp + 74 random bits.
 *
 * Chosen over UUIDv4 because the leading timestamp keeps B-tree index inserts
 * append-only in PostgreSQL, which matters for the high-write tables
 * (sessions, security_events, rate_limit_hits).
 */
final class UuidV7Generator implements IdGeneratorInterface
{
    public function __construct(private readonly ClockInterface $clock)
    {
    }

    public function generate(): string
    {
        $ms = (int) floor((float) $this->clock->now()->format('U.u') * 1000);

        $bytes = pack('J', $ms);          // 8 bytes, big endian
        $bytes = substr($bytes, 2);        // keep the low 48 bits
        $bytes .= random_bytes(10);

        // version 7
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        // RFC 9562 variant
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
