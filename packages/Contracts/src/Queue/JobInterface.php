<?php

declare(strict_types=1);

namespace Yume\Contracts\Queue;

interface JobInterface
{
    public function name(): string;

    /** @return array<string, mixed> */
    public function payload(): array;
}
