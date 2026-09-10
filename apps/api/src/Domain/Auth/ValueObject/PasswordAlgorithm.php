<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\ValueObject;

enum PasswordAlgorithm: string
{
    case Argon2id = 'argon2id';

    /**
     * Only ever read, never written.
     *
     * Present so the ~10 year old unsalted SHA-256 hashes can be upgraded during
     * a normal login instead of forcing a password reset on every account.
     */
    case LegacySha256 = 'legacy_sha256';
}
