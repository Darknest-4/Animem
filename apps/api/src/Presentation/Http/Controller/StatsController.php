<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Contracts\Cache\CacheInterface;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Contracts\Persistence\ConnectionInterface;

/**
 * Site statistics.
 *
 * Cached for a minute: these are counts over whole tables, and nobody needs
 * them to the second. The legacy home page ran the equivalent aggregates —
 * plus an `ORDER BY RAND()` over the full catalogue — on every single request.
 */
final class StatsController
{
    private const CACHE_KEY = 'stats.overview';
    private const CACHE_TTL = 60;

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly CacheInterface $cache,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function overview(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $stats = $this->cache->remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            return [
                'users' => [
                    'total' => $this->scalarInt('SELECT count(*) FROM users'),
                    'active' => $this->scalarInt("SELECT count(*) FROM users WHERE status = 'active'"),
                    'new_this_week' => $this->scalarInt(
                        "SELECT count(*) FROM users WHERE created_at >= now() - INTERVAL '7 days'",
                    ),
                ],
                'catalogue' => [
                    'anime_published' => $this->scalarInt('SELECT count(*) FROM anime WHERE is_published'),
                    'anime_drafts' => $this->scalarInt('SELECT count(*) FROM anime WHERE NOT is_published'),
                    'episodes' => $this->scalarInt('SELECT count(*) FROM episodes WHERE is_published'),
                    'releases' => $this->scalarInt('SELECT count(*) FROM episode_releases'),
                    'uploaders' => $this->scalarInt('SELECT count(*) FROM uploaders WHERE is_active'),
                ],
                'sessions' => [
                    'active' => $this->scalarInt(
                        'SELECT count(*) FROM sessions WHERE revoked_at IS NULL AND expires_at > now()',
                    ),
                ],
                'security' => [
                    'events_last_24h' => $this->scalarInt(
                        "SELECT count(*) FROM security_events WHERE occurred_at >= now() - INTERVAL '24 hours'",
                    ),
                    'critical_last_24h' => $this->scalarInt(
                        "SELECT count(*) FROM security_events
                         WHERE severity = 'critical' AND occurred_at >= now() - INTERVAL '24 hours'",
                    ),
                    'active_bans' => $this->scalarInt(
                        'SELECT count(*) FROM bans WHERE lifted_at IS NULL AND (expires_at IS NULL OR expires_at > now())',
                    ),
                ],
            ];
        });

        return JsonResponse::ok([
            'stats' => $stats,
            'generated_at' => $this->clock->now()->format(\DateTimeInterface::ATOM),
            'cache_ttl_seconds' => self::CACHE_TTL,
        ]);
    }

    private function scalarInt(string $sql): int
    {
        return (int) $this->connection->scalar($sql);
    }
}
