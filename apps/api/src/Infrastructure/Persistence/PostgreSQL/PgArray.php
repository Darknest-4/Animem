<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\PostgreSQL;

/**
 * Decodes PostgreSQL's array literal form.
 *
 * PDO hands back `{admin,user}` as a plain string rather than a PHP array, so
 * aggregated columns (role slugs, genre slugs) need unpacking. Kept in one place
 * because getting the quoting rules subtly wrong in three repositories is how
 * a genre called "Slice of Life, Drama" ends up as two genres.
 */
final class PgArray
{
    /** @return list<string> */
    public static function toStrings(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map('strval', $value));
        }

        if (!is_string($value) || $value === '' || $value === '{}') {
            return [];
        }

        $inner = substr($value, 1, -1);

        if ($inner === '' || $inner === false) {
            return [];
        }

        $items = [];
        $current = '';
        $inQuotes = false;
        $escaped = false;

        for ($i = 0, $length = strlen($inner); $i < $length; ++$i) {
            $character = $inner[$i];

            if ($escaped) {
                $current .= $character;
                $escaped = false;
                continue;
            }

            if ($character === '\\') {
                $escaped = true;
                continue;
            }

            if ($character === '"') {
                $inQuotes = !$inQuotes;
                continue;
            }

            // A comma inside quotes is part of the value, not a separator.
            if ($character === ',' && !$inQuotes) {
                $items[] = $current;
                $current = '';
                continue;
            }

            $current .= $character;
        }

        $items[] = $current;

        return array_values(array_filter($items, static fn (string $item): bool => $item !== ''));
    }
}
