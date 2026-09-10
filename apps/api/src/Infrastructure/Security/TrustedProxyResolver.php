<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Security;

use Yume\Api\Domain\Shared\ValueObject\IpAddress;

/**
 * Resolves the real client IP behind Caddy and nginx.
 *
 * The legacy `getip()` trusted HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR straight
 * from the client and wrote the result into SQL, so a visitor could choose their
 * own IP address and evade every per-IP control. Here a forwarding header is
 * honoured only when the immediate peer is a configured trusted proxy, and the
 * chain is walked from the right, dropping trusted hops.
 */
final class TrustedProxyResolver
{
    /** @param list<string> $trustedProxies CIDR blocks or exact addresses */
    public function __construct(private readonly array $trustedProxies = [])
    {
    }

    /** @param array<string, string> $headers lower-cased header names */
    public function resolve(string $remoteAddr, array $headers): IpAddress
    {
        $peer = IpAddress::tryFromString($remoteAddr);

        if ($peer === null) {
            // No usable peer address at all (CLI, malformed SAPI): fall back to a
            // documented sentinel rather than trusting a header.
            return IpAddress::fromString('127.0.0.1');
        }

        if (!$this->isTrusted($peer)) {
            return $peer;
        }

        $forwarded = $headers['x-forwarded-for'] ?? null;
        if ($forwarded === null || trim($forwarded) === '') {
            return IpAddress::tryFromString($headers['x-real-ip'] ?? '') ?? $peer;
        }

        $chain = array_values(array_filter(array_map('trim', explode(',', $forwarded))));

        // Walk right-to-left: each rightmost entry was appended by a hop we trust,
        // so the first untrusted address is the real client.
        for ($i = count($chain) - 1; $i >= 0; --$i) {
            $candidate = IpAddress::tryFromString($chain[$i]);

            if ($candidate === null) {
                continue;
            }

            if (!$this->isTrusted($candidate)) {
                return $candidate;
            }
        }

        return $peer;
    }

    private function isTrusted(IpAddress $ip): bool
    {
        foreach ($this->trustedProxies as $trusted) {
            if (self::matches($ip->value, $trusted)) {
                return true;
            }
        }

        return false;
    }

    private static function matches(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range, 2);
        $bits = (int) $bits;

        $ipBinary = inet_pton($ip);
        $subnetBinary = inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $wholeBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        if ($wholeBytes > 0 && strncmp($ipBinary, $subnetBinary, $wholeBytes) !== 0) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = ~((1 << (8 - $remainingBits)) - 1) & 0xFF;

        return (ord($ipBinary[$wholeBytes]) & $mask) === (ord($subnetBinary[$wholeBytes]) & $mask);
    }
}
