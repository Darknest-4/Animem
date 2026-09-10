<?php

declare(strict_types=1);

namespace Yume\Api\Application\Feature;

use Yume\Api\Domain\Feature\Entity\FeatureFlag;
use Yume\Api\Domain\Feature\Repository\FeatureFlagRepositoryInterface;
use Yume\Api\Domain\Feature\ValueObject\FlagKey;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Feature\FeatureFlagsInterface;
use Yume\Contracts\Logging\LoggerInterface;
use Yume\Shared\Support\Env;

/**
 * Resolution order, highest priority first:
 *
 *   1. environment override  (FEATURE_<KEY>=true)  — local development, break-glass
 *   2. database record       (admin UI, no deploy) — the normal switch
 *   3. code default          (false)               — an unknown flag is off
 *
 * The whole flag table is loaded once per request, not once per lookup: a page
 * checking eight flags must not cost eight round trips.
 */
final class FeatureFlagResolver implements FeatureFlagsInterface
{
    /** @var array<string, FeatureFlag>|null */
    private ?array $flags = null;

    public function __construct(
        private readonly FeatureFlagRepositoryInterface $repository,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly bool $allowEnvOverrides = true,
    ) {
    }

    public function enabled(string $flagKey, array $context = []): bool
    {
        try {
            $key = FlagKey::fromString($flagKey);
        } catch (\InvalidArgumentException) {
            return false;
        }

        if ($this->allowEnvOverrides) {
            $override = Env::get($key->envName());
            if ($override !== null) {
                return in_array(strtolower($override), ['1', 'true', 'yes', 'on'], true);
            }
        }

        $flag = $this->load()[$key->value] ?? null;

        if ($flag === null) {
            return false;
        }

        if ($flag->isExpired($this->clock->now())) {
            // Still evaluated — flipping a flag under live traffic because someone
            // forgot to clean it up would be worse than the stale flag itself.
            $this->logger->warning('Feature flag past its expiry date is still being evaluated.', [
                'flag' => $key->value,
                'expired_at' => $flag->expiresAt()?->format(\DateTimeInterface::ATOM),
            ]);
        }

        return $flag->isEnabledFor($context);
    }

    public function all(array $context = []): array
    {
        $result = [];

        foreach ($this->load() as $key => $flag) {
            $result[$key] = $this->enabled($key, $context);
        }

        return $result;
    }

    /** Drops the per-request memo; used by the admin write path and by tests. */
    public function refresh(): void
    {
        $this->flags = null;
    }

    /** @return array<string, FeatureFlag> */
    private function load(): array
    {
        if ($this->flags !== null) {
            return $this->flags;
        }

        $flags = [];
        foreach ($this->repository->all() as $flag) {
            $flags[$flag->key->value] = $flag;
        }

        return $this->flags = $flags;
    }
}
