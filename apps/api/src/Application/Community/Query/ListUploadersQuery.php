<?php

declare(strict_types=1);

namespace Yume\Api\Application\Community\Query;

use Yume\Contracts\Bus\QueryInterface;

final readonly class ListUploadersQuery implements QueryInterface
{
    public function __construct(public bool $includeInactive = false)
    {
    }
}
