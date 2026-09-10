<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\ValueObject;

enum Season: string
{
    case Winter = 'winter';
    case Spring = 'spring';
    case Summer = 'summer';
    case Fall = 'fall';

    public static function forMonth(int $month): self
    {
        return match (true) {
            $month <= 3 => self::Winter,
            $month <= 6 => self::Spring,
            $month <= 9 => self::Summer,
            default => self::Fall,
        };
    }
}
