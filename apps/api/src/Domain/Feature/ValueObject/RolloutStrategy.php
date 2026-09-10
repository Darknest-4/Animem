<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Feature\ValueObject;

enum RolloutStrategy: string
{
    /** Hard off, regardless of any other field. */
    case Off = 'off';
    /** Hard on for everyone. */
    case On = 'on';
    /** Deterministic bucketing by actor, 0-100%. */
    case Percentage = 'percentage';
    /** On for the role slugs listed in the payload. */
    case Role = 'role';
    /** On for the user ids listed in the payload. */
    case UserList = 'user_list';
    /** On for the IP addresses listed in the payload. */
    case IpList = 'ip_list';
}
