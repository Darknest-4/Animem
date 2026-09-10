<?php

declare(strict_types=1);

namespace Yume\Contracts\Queue;

interface QueueInterface
{
    public function push(JobInterface $job, int $delaySeconds = 0): string;

    /** @return array{id: string, name: string, payload: array<string, mixed>, attempts: int}|null */
    public function reserve(int $timeoutSeconds = 5): ?array;

    public function complete(string $jobId): void;

    public function fail(string $jobId, string $reason, bool $retry = true): void;
}
