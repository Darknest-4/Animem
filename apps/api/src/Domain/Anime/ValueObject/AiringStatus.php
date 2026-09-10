<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\ValueObject;

enum AiringStatus: string
{
    case Airing = 'airing';
    case Finished = 'finished';
    case Upcoming = 'upcoming';
    case Cancelled = 'cancelled';
}
