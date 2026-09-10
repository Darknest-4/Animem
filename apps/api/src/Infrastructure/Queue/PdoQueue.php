<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Queue;

use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Contracts\Queue\JobInterface;
use Yume\Contracts\Queue\QueueInterface;
use Yume\Shared\Support\Json;

/**
 * PostgreSQL-backed job queue.
 *
 * Reservation uses FOR UPDATE SKIP LOCKED so several worker containers can pull
 * from the same queue without double-processing and without a distributed lock.
 */
final class PdoQueue implements QueueInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
        private readonly string $queue = 'default',
        private readonly int $maxAttempts = 5,
    ) {
    }

    public function push(JobInterface $job, int $delaySeconds = 0): string
    {
        $id = $this->ids->generate();

        $this->connection->execute(
            <<<'SQL'
            INSERT INTO jobs (id, queue, name, payload, max_attempts, available_at)
            VALUES (:id, :queue, :name, CAST(:payload AS jsonb), :max_attempts, :available_at)
            SQL,
            [
                'id' => $id,
                'queue' => $this->queue,
                'name' => $job->name(),
                'payload' => Json::encode($job->payload()),
                'max_attempts' => $this->maxAttempts,
                'available_at' => $this->clock->now()
                    ->modify(sprintf('+%d seconds', max(0, $delaySeconds)))
                    ->format('Y-m-d H:i:sP'),
            ],
        );

        return $id;
    }

    public function reserve(int $timeoutSeconds = 5): ?array
    {
        $now = $this->clock->now();

        $row = $this->connection->selectOne(
            <<<'SQL'
            UPDATE jobs
            SET reserved_at = :now, attempts = attempts + 1
            WHERE id = (
                SELECT id FROM jobs
                WHERE queue = :queue
                  AND reserved_at IS NULL
                  AND completed_at IS NULL
                  AND failed_at IS NULL
                  AND available_at <= :now
                ORDER BY available_at
                FOR UPDATE SKIP LOCKED
                LIMIT 1
            )
            RETURNING id, name, payload, attempts
            SQL,
            ['queue' => $this->queue, 'now' => $now->format('Y-m-d H:i:sP')],
        );

        if ($row === null) {
            return null;
        }

        return [
            'id' => (string) $row['id'],
            'name' => (string) $row['name'],
            'payload' => Json::decodeToArrayOrEmpty((string) ($row['payload'] ?? '{}')),
            'attempts' => (int) $row['attempts'],
        ];
    }

    public function complete(string $jobId): void
    {
        $this->connection->execute(
            'UPDATE jobs SET completed_at = :now, reserved_at = NULL WHERE id = :id',
            ['id' => $jobId, 'now' => $this->clock->now()->format('Y-m-d H:i:sP')],
        );
    }

    public function fail(string $jobId, string $reason, bool $retry = true): void
    {
        $now = $this->clock->now();

        if (!$retry) {
            $this->connection->execute(
                'UPDATE jobs SET failed_at = :now, last_error = :reason, reserved_at = NULL WHERE id = :id',
                ['id' => $jobId, 'now' => $now->format('Y-m-d H:i:sP'), 'reason' => $reason],
            );

            return;
        }

        // Exponential backoff, and a job that has burned its attempts is parked
        // rather than retried forever.
        $this->connection->execute(
            <<<'SQL'
            UPDATE jobs
            SET reserved_at  = NULL,
                last_error   = :reason,
                available_at = :now + (INTERVAL '1 second' * LEAST(3600, POWER(2, attempts) * 10)),
                failed_at    = CASE WHEN attempts >= max_attempts THEN :now ELSE NULL END
            WHERE id = :id
            SQL,
            ['id' => $jobId, 'now' => $now->format('Y-m-d H:i:sP'), 'reason' => $reason],
        );
    }
}
