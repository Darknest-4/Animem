<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Episode\ValueObject;

enum ReleaseKind: string
{
    case Sub = 'sub';
    case Dub = 'dub';
    case Raw = 'raw';
}
