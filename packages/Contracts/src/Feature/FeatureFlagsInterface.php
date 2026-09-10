<?php

declare(strict_types=1);

namespace Yume\Contracts\Feature;

interface FeatureFlagsInterface
{
    /** @param array<string, mixed> $context user_id, roles, ip … */
    public function enabled(string $flagKey, array $context = []): bool;

    /** @return array<string, bool> */
    public function all(array $context = []): array;
}
