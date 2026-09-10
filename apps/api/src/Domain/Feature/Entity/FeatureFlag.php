<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Feature\Entity;

use Yume\Api\Domain\Feature\ValueObject\FlagKey;
use Yume\Api\Domain\Feature\ValueObject\RolloutStrategy;

/**
 * A runtime-switchable feature flag.
 *
 * This is the mechanism the legacy codebase was missing: it had four unfinished
 * rewrites gated by commented-out code, a hardcoded `$newSite` array and a
 * hardcoded IP address. With flags, an unfinished rewrite can ship dark at 0%
 * and be widened without a deploy.
 */
final class FeatureFlag
{
    private function __construct(
        public readonly FlagKey $key,
        private string $name,
        private ?string $description,
        private RolloutStrategy $strategy,
        private int $rolloutPercentage,
        /** @var array<string, mixed> */
        private array $payload,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private ?\DateTimeImmutable $expiresAt,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function reconstitute(
        FlagKey $key,
        string $name,
        ?string $description,
        RolloutStrategy $strategy,
        int $rolloutPercentage,
        array $payload,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            $key,
            $name,
            $description,
            $strategy,
            max(0, min(100, $rolloutPercentage)),
            $payload,
            $createdAt,
            $updatedAt,
            $expiresAt,
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function strategy(): RolloutStrategy
    {
        return $this->strategy;
    }

    public function rolloutPercentage(): int
    {
        return $this->rolloutPercentage;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * A flag past its expiry is a flag nobody cleaned up. It reports as expired so
     * the worker can nag, but it keeps evaluating rather than flipping under traffic.
     */
    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expiresAt instanceof \DateTimeImmutable && $this->expiresAt <= $now;
    }

    /**
     * @param array<string, mixed> $context user_id, roles, ip
     */
    public function isEnabledFor(array $context): bool
    {
        return match ($this->strategy) {
            RolloutStrategy::Off => false,
            RolloutStrategy::On => true,
            RolloutStrategy::Percentage => $this->matchesPercentage($context),
            RolloutStrategy::Role => $this->matchesRole($context),
            RolloutStrategy::UserList => $this->matchesUserList($context),
            RolloutStrategy::IpList => $this->matchesIpList($context),
        };
    }

    /** @param array<string, mixed> $context */
    private function matchesPercentage(array $context): bool
    {
        if ($this->rolloutPercentage <= 0) {
            return false;
        }

        if ($this->rolloutPercentage >= 100) {
            return true;
        }

        // Deterministic per actor and per flag: the same user keeps the same answer
        // as the percentage grows, and two flags at 10% do not select the same cohort.
        $actor = (string) ($context['user_id'] ?? $context['ip'] ?? '');
        if ($actor === '') {
            return false;
        }

        $bucket = hexdec(substr(hash('sha256', $this->key->value . ':' . $actor), 0, 8)) % 100;

        return $bucket < $this->rolloutPercentage;
    }

    /** @param array<string, mixed> $context */
    private function matchesRole(array $context): bool
    {
        $allowed = $this->payloadList('roles');
        $actorRoles = $context['roles'] ?? [];

        if (!is_array($actorRoles)) {
            return false;
        }

        return array_intersect($allowed, array_map('strval', $actorRoles)) !== [];
    }

    /** @param array<string, mixed> $context */
    private function matchesUserList(array $context): bool
    {
        $userId = $context['user_id'] ?? null;

        return is_string($userId) && in_array($userId, $this->payloadList('users'), true);
    }

    /** @param array<string, mixed> $context */
    private function matchesIpList(array $context): bool
    {
        $ip = $context['ip'] ?? null;

        return is_string($ip) && in_array($ip, $this->payloadList('ips'), true);
    }

    /** @return list<string> */
    private function payloadList(string $key): array
    {
        $value = $this->payload[$key] ?? [];

        return is_array($value) ? array_values(array_map('strval', $value)) : [];
    }
}
