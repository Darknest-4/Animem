<?php

declare(strict_types=1);

namespace Yume\Contracts\Bus;

interface CommandBusInterface
{
    public function dispatch(CommandInterface $command): mixed;
}
