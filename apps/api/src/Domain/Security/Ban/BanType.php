<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Ban;

enum BanType: string
{
    /** Set by the risk engine; lifts automatically. */
    case Automatic = 'automatic';
    /** Set by a moderator; lifts only when a moderator lifts it. */
    case Manual = 'manual';
    /** Read-only access retained, writes refused. */
    case ReadOnly = 'read_only';
}
