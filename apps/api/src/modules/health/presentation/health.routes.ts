import { z } from 'zod';

import type { Services } from '../../../container/services.js';
import { access } from '../../../http/access.js';
import { defineRoute, type RouteDefinition } from '../../../http/route.js';

const TAGS = ['health'] as const;

const livenessSchema = z.object({
  status: z.literal('ok'),
  uptime_seconds: z.number(),
});

const readinessSchema = z.object({
  status: z.enum(['ready', 'degraded']),
  checks: z.record(z.object({ ok: z.boolean(), duration_ms: z.number() })),
});

/**
 * Liveness and readiness, kept separate on purpose.
 *
 * Liveness answers "is this process wedged?" and must not touch a dependency: if
 * it checked the database, a database blip would make the orchestrator kill every
 * healthy API container, turning a recoverable outage into a restart storm.
 * Readiness answers "should traffic come here?" and is where dependencies belong.
 */
export function healthRoutes(services: Services): readonly RouteDefinition[] {
  return [
    defineRoute({
      method: 'GET',
      url: '/health/live',
      access: access.public(),
      schema: {
        summary: 'Liveness probe',
        tags: TAGS,
        response: { 200: livenessSchema },
      },
      async handler(_request, reply) {
        return reply.status(200).send({ status: 'ok', uptime_seconds: Math.round(process.uptime()) });
      },
    }),

    defineRoute({
      method: 'GET',
      url: '/health/ready',
      access: access.public(),
      schema: {
        summary: 'Readiness probe',
        tags: TAGS,
        response: { 200: readinessSchema, 503: readinessSchema },
      },
      async handler(_request, reply) {
        const database = await timed(() => services.database.ping());
        const ready = database.ok;

        return reply
          .status(ready ? 200 : 503)
          .send({ status: ready ? 'ready' : 'degraded', checks: { database } });
      },
    }),
  ];
}

async function timed(check: () => Promise<unknown>): Promise<{ ok: boolean; duration_ms: number }> {
  const startedAt = performance.now();

  try {
    await check();

    return { ok: true, duration_ms: Math.round(performance.now() - startedAt) };
  } catch {
    return { ok: false, duration_ms: Math.round(performance.now() - startedAt) };
  }
}
