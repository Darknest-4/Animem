<?php

declare(strict_types=1);

namespace Yume\Security\Csrf;

/**
 * Double-submit CSRF tokens bound to the session with an HMAC.
 *
 * Required because the session cookie is SameSite=Lax rather than Strict: Lax
 * still allows top-level cross-site POSTs in some browsers. None of the legacy
 * site's 27 forms carried a token, and its delete actions ran over GET.
 */
final class CsrfTokenManager
{
    public function __construct(private readonly string $secret)
    {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('CSRF secret must be at least 32 bytes.');
        }
    }

    public function generate(string $sessionId): string
    {
        $random = bin2hex(random_bytes(16));

        return $random . '.' . $this->sign($sessionId, $random);
    }

    public function isValid(string $sessionId, ?string $token): bool
    {
        if ($token === null || !str_contains($token, '.')) {
            return false;
        }

        [$random, $signature] = explode('.', $token, 2);

        return hash_equals($this->sign($sessionId, $random), $signature);
    }

    private function sign(string $sessionId, string $random): string
    {
        return hash_hmac('sha256', $sessionId . '|' . $random, $this->secret);
    }
}
