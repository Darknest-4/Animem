<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Contracts\Persistence\ConnectionInterface;

/**
 * Liveness and readiness, kept separate on purpose.
 *
 * `/health` answers "is this process alive" and must not touch the database:
 * an orchestrator that restarts every API container because Postgres blipped
 * turns a degraded system into an outage. `/health/ready` answers "should this
 * container receive traffic" and does check dependencies.
 */
final class HealthController
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly ClockInterface $clock,
        private readonly string $version = 'dev',
    ) {
    }

    /** @param array<string, string> $parameters */
    public function live(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        return JsonResponse::ok([
            'status' => 'ok',
            'version' => $this->version,
            'time' => $this->clock->now()->format(\DateTimeInterface::ATOM),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function ready(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $checks = ['database' => $this->checkDatabase()];
        $healthy = !in_array(false, array_column($checks, 'healthy'), true);

        return JsonResponse::withStatusAndPayload($healthy ? 200 : 503, [
            'status' => $healthy ? 'ready' : 'degraded',
            'version' => $this->version,
            'checks' => $checks,
            'time' => $this->clock->now()->format(\DateTimeInterface::ATOM),
        ]);
    }

    /** @return array{healthy: bool, latency_ms: float|null, error: string|null} */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            $this->connection->scalar('SELECT 1');

            return [
                'healthy' => true,
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'error' => null,
            ];
        } catch (\Throwable) {
            // The reason is logged elsewhere; the probe body stays generic because
            // readiness endpoints are often reachable from outside the cluster.
            return ['healthy' => false, 'latency_ms' => null, 'error' => 'unavailable'];
        }
    }
}
