<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Query;

use Yume\Contracts\Bus\QueryInterface;

final readonly class GetCurrentUserQuery implements QueryInterface
{
    public function __construct(public string $userId)
    {
    }
}
