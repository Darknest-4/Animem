<?php

declare(strict_types=1);

namespace Yume\Security\Token;

final readonly class GeneratedToken
{
    public function __construct(
        /** Shown to the client once; never persisted. */
        public string $plain,
        /** Persisted; safe to index and to leak. */
        public string $hash,
    ) {
    }
}
