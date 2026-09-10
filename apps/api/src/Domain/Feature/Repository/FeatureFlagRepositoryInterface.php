<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Feature\Repository;

use Yume\Api\Domain\Feature\Entity\FeatureFlag;
use Yume\Api\Domain\Feature\ValueObject\FlagKey;

interface FeatureFlagRepositoryInterface
{
    public function find(FlagKey $key): ?FeatureFlag;

    /** @return list<FeatureFlag> */
    public function all(): array;

    public function save(FeatureFlag $flag): void;
}
