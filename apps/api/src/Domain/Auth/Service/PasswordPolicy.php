<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Auth\Service;

use Yume\Api\Domain\Auth\Exception\WeakPasswordException;

/**
 * Password rules, expressed as domain logic rather than as a regex in a controller.
 *
 * Deliberately length-first and blocklist-based rather than composition-based
 * ("must contain an uppercase letter and a symbol"), which pushes users toward
 * predictable substitutions. Follows NIST SP 800-63B guidance.
 */
final class PasswordPolicy
{
    /** @param list<string> $blocklist */
    public function __construct(
        private readonly int $minimumLength = 12,
        private readonly int $maximumLength = 4096,
        private readonly array $blocklist = [
            'password', 'passw0rd', 'jelszo', 'jelszó', '123456', '12345678', '123456789',
            'qwerty', 'qwertz', 'abc123', 'iloveyou', 'admin', 'letmein', 'welcome',
            'animem', 'yume', 'anime',
        ],
    ) {
    }

    /**
     * @param list<string> $personalData username, email … must not appear in the password
     * @throws WeakPasswordException
     */
    public function assertAcceptable(string $password, array $personalData = []): void
    {
        $length = mb_strlen($password);

        if ($length < $this->minimumLength) {
            throw WeakPasswordException::tooShort($this->minimumLength);
        }

        if ($length > $this->maximumLength) {
            // Argon2id on an unbounded input is a cheap denial of service.
            throw WeakPasswordException::tooLong($this->maximumLength);
        }

        $normalised = mb_strtolower($password);

        foreach ($this->blocklist as $blocked) {
            if (str_contains($normalised, $blocked)) {
                throw WeakPasswordException::blocklisted();
            }
        }

        foreach ($personalData as $datum) {
            $datum = mb_strtolower(trim($datum));
            if ($datum !== '' && mb_strlen($datum) >= 4 && str_contains($normalised, $datum)) {
                throw WeakPasswordException::containsPersonalData();
            }
        }

        if (self::isSingleRepeatedCharacter($normalised) || self::isSequential($normalised)) {
            throw WeakPasswordException::tooPredictable();
        }
    }

    private static function isSingleRepeatedCharacter(string $password): bool
    {
        return count(array_unique(mb_str_split($password))) <= 2;
    }

    private static function isSequential(string $password): bool
    {
        $characters = mb_str_split($password);
        $ascending = 0;
        $descending = 0;

        for ($i = 1, $count = count($characters); $i < $count; ++$i) {
            $delta = mb_ord($characters[$i]) - mb_ord($characters[$i - 1]);
            $ascending += $delta === 1 ? 1 : 0;
            $descending += $delta === -1 ? 1 : 0;
        }

        $threshold = max(4, (int) floor(count($characters) * 0.8));

        return $ascending >= $threshold || $descending >= $threshold;
    }
}
