<?php

declare(strict_types=1);

namespace Yume\Contracts\Identity;

interface IdGeneratorInterface
{
    /** Returns a lexicographically sortable, globally unique identifier. */
    public function generate(): string;
}
