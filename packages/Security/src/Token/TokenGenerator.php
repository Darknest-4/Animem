<?php

declare(strict_types=1);

namespace Yume\Security\Token;

/**
 * Opaque bearer tokens: the plaintext is returned to the client exactly once,
 * only its SHA-256 digest is stored.
 *
 * A database leak therefore does not hand the attacker usable session tokens,
 * and lookups still work because the digest is deterministic.
 */
final class TokenGenerator
{
    public function __construct(private readonly int $byteLength = 32)
    {
    }

    public function generate(): GeneratedToken
    {
        $plain = rtrim(strtr(base64_encode(random_bytes($this->byteLength)), '+/', '-_'), '=');

        return new GeneratedToken($plain, self::hash($plain));
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function matches(string $plainToken, string $storedHash): bool
    {
        return hash_equals($storedHash, self::hash($plainToken));
    }
}
