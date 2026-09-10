<?php

declare(strict_types=1);

namespace Yume\Contracts\Bus;

interface QueryBusInterface
{
    public function ask(QueryInterface $query): mixed;
}
