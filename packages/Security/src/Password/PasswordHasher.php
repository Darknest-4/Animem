<?php

declare(strict_types=1);

namespace Yume\Security\Password;

/**
 * Argon2id password hashing with transparent rehash-on-login.
 *
 * The legacy site stored unsalted single-round SHA-256 and ran the plaintext
 * through htmlspecialchars() first, which silently mangled any password
 * containing < > & or ". Both mistakes are impossible here: the plaintext is
 * never transformed, and {@see verifyLegacySha256()} exists solely to let old
 * hashes be upgraded during a normal login.
 */
final class PasswordHasher
{
    /** @param array<string, int> $options */
    public function __construct(
        private readonly string $algorithm = PASSWORD_ARGON2ID,
        private readonly array $options = [
            'memory_cost' => 65536,  // 64 MiB
            'time_cost' => 4,
            'threads' => 2,
        ],
    ) {
    }

    public function hash(string $plainPassword): string
    {
        $hash = password_hash($plainPassword, $this->algorithm, $this->options);

        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed.');
        }

        return $hash;
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }

    /**
     * Verifies a hash produced by the legacy `hash('sha256', $password)` call.
     *
     * Used only on the migration path: a successful match is immediately
     * re-hashed with Argon2id and the legacy column is cleared.
     */
    public function verifyLegacySha256(string $plainPassword, string $legacyHash): bool
    {
        return hash_equals(strtolower($legacyHash), hash('sha256', $plainPassword));
    }
}
