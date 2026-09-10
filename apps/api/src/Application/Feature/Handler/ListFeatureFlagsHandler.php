<?php

declare(strict_types=1);

namespace Yume\Api\Application\Feature\Handler;

use Yume\Api\Application\Feature\FeatureFlagResolver;
use Yume\Api\Application\Feature\Query\ListFeatureFlagsQuery;
use Yume\Api\Domain\Feature\Entity\FeatureFlag;
use Yume\Api\Domain\Feature\Repository\FeatureFlagRepositoryInterface;

final class ListFeatureFlagsHandler
{
    public function __construct(
        private readonly FeatureFlagResolver $resolver,
        private readonly FeatureFlagRepositoryInterface $repository,
    ) {
    }

    /** @return array<string, mixed> */
    public function __invoke(ListFeatureFlagsQuery $query): array
    {
        $context = array_filter([
            'user_id' => $query->userId,
            'roles' => $query->roles,
            'ip' => $query->ip,
        ], static fn (mixed $v): bool => $v !== null && $v !== []);

        $states = $this->resolver->all($context);

        if (!$query->includeDefinition) {
            return $states;
        }

        $detailed = [];
        foreach ($this->repository->all() as $flag) {
            $detailed[$flag->key->value] = $this->describe($flag, $states[$flag->key->value] ?? false);
        }

        return $detailed;
    }

    /** @return array<string, mixed> */
    private function describe(FeatureFlag $flag, bool $enabledForCaller): array
    {
        return [
            'key' => $flag->key->value,
            'name' => $flag->name(),
            'description' => $flag->description(),
            'strategy' => $flag->strategy()->value,
            'rollout_percentage' => $flag->rolloutPercentage(),
            // An empty PHP array encodes as [], which would make the payload's
            // JSON type flip between object and array depending on content.
            'payload' => $flag->payload() === [] ? new \stdClass() : $flag->payload(),
            'enabled_for_caller' => $enabledForCaller,
            'expires_at' => $flag->expiresAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $flag->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
