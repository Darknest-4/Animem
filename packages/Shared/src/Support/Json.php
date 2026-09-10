<?php

declare(strict_types=1);

namespace Yume\Shared\Support;

final class Json
{
    public static function encode(mixed $value, bool $pretty = false): string
    {
        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($value, $flags);
    }

    /** @return array<string, mixed> */
    public static function decodeToArray(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, mixed> */
    public static function decodeToArrayOrEmpty(string $json): array
    {
        try {
            return self::decodeToArray($json);
        } catch (\JsonException) {
            return [];
        }
    }
}
