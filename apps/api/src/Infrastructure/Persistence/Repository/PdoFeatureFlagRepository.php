<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Feature\Entity\FeatureFlag;
use Yume\Api\Domain\Feature\Repository\FeatureFlagRepositoryInterface;
use Yume\Api\Domain\Feature\ValueObject\FlagKey;
use Yume\Api\Domain\Feature\ValueObject\RolloutStrategy;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Shared\Support\Json;

final class PdoFeatureFlagRepository implements FeatureFlagRepositoryInterface
{
    private const SELECT = <<<'SQL'
        SELECT flag_key, name, description, strategy, rollout_percentage,
               payload, created_at, updated_at, expires_at
        FROM feature_flags
        SQL;

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function find(FlagKey $key): ?FeatureFlag
    {
        $row = $this->connection->selectOne(
            self::SELECT . ' WHERE flag_key = :key',
            ['key' => $key->value],
        );

        return $row === null ? null : self::hydrate($row);
    }

    public function all(): array
    {
        return array_map(self::hydrate(...), $this->connection->select(self::SELECT . ' ORDER BY flag_key'));
    }

    public function save(FeatureFlag $flag): void
    {
        $this->connection->execute(
            <<<'SQL'
            INSERT INTO feature_flags (flag_key, name, description, strategy,
                                       rollout_percentage, payload, created_at, updated_at, expires_at)
            VALUES (:key, :name, :description, :strategy,
                    :rollout, CAST(:payload AS jsonb), :created_at, :updated_at, :expires_at)
            ON CONFLICT (flag_key) DO UPDATE SET
                name               = EXCLUDED.name,
                description        = EXCLUDED.description,
                strategy           = EXCLUDED.strategy,
                rollout_percentage = EXCLUDED.rollout_percentage,
                payload            = EXCLUDED.payload,
                updated_at         = EXCLUDED.updated_at,
                expires_at         = EXCLUDED.expires_at
            SQL,
            [
                'key' => $flag->key->value,
                'name' => $flag->name(),
                'description' => $flag->description(),
                'strategy' => $flag->strategy()->value,
                'rollout' => $flag->rolloutPercentage(),
                'payload' => Json::encode($flag->payload()),
                'created_at' => $flag->createdAt->format('Y-m-d H:i:sP'),
                'updated_at' => $flag->updatedAt()->format('Y-m-d H:i:sP'),
                'expires_at' => $flag->expiresAt()?->format('Y-m-d H:i:sP'),
            ],
        );
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): FeatureFlag
    {
        return FeatureFlag::reconstitute(
            FlagKey::fromString((string) $row['flag_key']),
            (string) $row['name'],
            is_string($row['description'] ?? null) ? (string) $row['description'] : null,
            RolloutStrategy::from((string) $row['strategy']),
            (int) $row['rollout_percentage'],
            Json::decodeToArrayOrEmpty((string) ($row['payload'] ?? '{}')),
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['updated_at']),
            is_string($row['expires_at'] ?? null) ? new \DateTimeImmutable((string) $row['expires_at']) : null,
        );
    }
}
