<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Feature\FeatureFlagResolver;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Feature\Entity\FeatureFlag;
use Yume\Api\Domain\Feature\Repository\FeatureFlagRepositoryInterface;
use Yume\Api\Domain\Feature\ValueObject\FlagKey;
use Yume\Api\Domain\Feature\ValueObject\RolloutStrategy;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Shared\Support\Json;

/**
 * Changing a feature flag without a deploy — the point of having flags at all.
 *
 * Writes are recorded in feature_flag_audit with the before and after state, so
 * "who widened the rollout right before the incident" is answerable.
 */
final class AdminFeatureController
{
    public function __construct(
        private readonly FeatureFlagRepositoryInterface $flags,
        private readonly FeatureFlagResolver $resolver,
        private readonly SecurityAuditor $auditor,
        private readonly ConnectionInterface $connection,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function index(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        return JsonResponse::ok([
            'features' => array_map($this->present(...), $this->flags->all()),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function update(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $key = FlagKey::fromString($parameters['key'] ?? '');
        $existing = $this->flags->find($key);

        if ($existing === null) {
            return ProblemDetails::make(404, 'feature_flag.not_found', sprintf('Feature flag "%s" does not exist.', $key->value));
        }

        $body = $request->body();

        $strategy = isset($body['strategy'])
            ? RolloutStrategy::tryFrom((string) $body['strategy'])
            : $existing->strategy();

        if ($strategy === null) {
            return ProblemDetails::validation(['strategy' => [
                'Must be one of: ' . implode(', ', array_column(RolloutStrategy::cases(), 'value')),
            ]]);
        }

        $percentage = isset($body['rollout_percentage'])
            ? (int) $body['rollout_percentage']
            : $existing->rolloutPercentage();

        if ($percentage < 0 || $percentage > 100) {
            return ProblemDetails::validation(['rollout_percentage' => ['Must be between 0 and 100.']]);
        }

        $payload = isset($body['payload']) && is_array($body['payload'])
            ? $body['payload']
            : $existing->payload();

        $expiresAt = $existing->expiresAt();
        if (array_key_exists('expires_at', $body)) {
            $raw = $body['expires_at'];
            if ($raw === null || $raw === '') {
                $expiresAt = null;
            } else {
                try {
                    $expiresAt = new \DateTimeImmutable((string) $raw);
                } catch (\Exception) {
                    return ProblemDetails::validation(['expires_at' => ['Must be an RFC 3339 timestamp, or null.']]);
                }
            }
        }

        $before = $this->present($existing);

        $updated = FeatureFlag::reconstitute(
            $key,
            isset($body['name']) ? (string) $body['name'] : $existing->name(),
            array_key_exists('description', $body) ? ($body['description'] === null ? null : (string) $body['description']) : $existing->description(),
            $strategy,
            $percentage,
            $payload,
            $existing->createdAt,
            $this->clock->now(),
            $expiresAt,
        );

        $actingUserId = $this->actingUserId($request);

        $this->connection->transaction(function () use ($updated, $before, $actingUserId, $key): void {
            $this->flags->save($updated);

            $this->connection->execute(
                <<<'SQL'
                INSERT INTO feature_flag_audit (id, flag_key, changed_by, before_state, after_state)
                VALUES (:id, :flag_key, :changed_by, CAST(:before AS jsonb), CAST(:after AS jsonb))
                SQL,
                [
                    'id' => $this->ids->generate(),
                    'flag_key' => $key->value,
                    'changed_by' => $actingUserId?->value,
                    'before' => Json::encode($before),
                    'after' => Json::encode($this->present($updated)),
                ],
            );
        });

        // The resolver memoises the whole table per request; drop it so the
        // response reflects the write that just happened.
        $this->resolver->refresh();

        $this->auditor->record(
            SecurityEventType::FeatureFlagChanged,
            IpAddress::fromString($request->ip()),
            UserAgent::fromString($request->userAgent()),
            $request->method(),
            $request->path(),
            $actingUserId,
            0,
            ['flag' => $key->value, 'strategy' => $strategy->value, 'rollout_percentage' => $percentage],
        );

        return JsonResponse::ok(['feature' => $this->present($updated)]);
    }

    /** @param array<string, string> $parameters */
    public function history(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $key = FlagKey::fromString($parameters['key'] ?? '');

        $rows = $this->connection->select(
            <<<'SQL'
            SELECT a.id, a.changed_at, a.before_state, a.after_state, u.username AS changed_by
            FROM feature_flag_audit a
                LEFT JOIN users u ON u.id = a.changed_by
            WHERE a.flag_key = :key
            ORDER BY a.changed_at DESC
            LIMIT 50
            SQL,
            ['key' => $key->value],
        );

        return JsonResponse::ok([
            'flag_key' => $key->value,
            'history' => array_map(
                static fn (array $row): array => [
                    'id' => (string) $row['id'],
                    'changed_at' => (string) $row['changed_at'],
                    'changed_by' => is_string($row['changed_by'] ?? null) ? (string) $row['changed_by'] : null,
                    'before' => Json::decodeToArrayOrEmpty((string) ($row['before_state'] ?? '{}')),
                    'after' => Json::decodeToArrayOrEmpty((string) ($row['after_state'] ?? '{}')),
                ],
                $rows,
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function present(FeatureFlag $flag): array
    {
        return [
            'key' => $flag->key->value,
            'name' => $flag->name(),
            'description' => $flag->description(),
            'strategy' => $flag->strategy()->value,
            'rollout_percentage' => $flag->rolloutPercentage(),
            'payload' => $flag->payload() === [] ? new \stdClass() : $flag->payload(),
            'expires_at' => $flag->expiresAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $flag->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function actingUserId(RequestInterface $request): ?UserId
    {
        $raw = $request->attribute('user_id');

        return is_string($raw) ? UserId::fromString($raw) : null;
    }
}
