<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Ban;

enum BanScope: string
{
    case Ip = 'ip';
    case Subnet = 'subnet';
    case User = 'user';
    case Global = 'global';
}
