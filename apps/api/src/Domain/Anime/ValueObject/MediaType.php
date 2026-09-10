<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\ValueObject;

enum MediaType: string
{
    case Tv = 'tv';
    case Movie = 'movie';
    case Ova = 'ova';
    case Ona = 'ona';
    case Special = 'special';
    case Music = 'music';

    /** A movie has one "episode"; the others are open-ended. */
    public function hasEpisodeList(): bool
    {
        return $this !== self::Movie && $this !== self::Music;
    }
}
