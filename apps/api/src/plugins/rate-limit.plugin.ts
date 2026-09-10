import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';

import type { Services } from '../container/services.js';
import { RateLimiter } from '../modules/security/application/rate-limiter.service.js';

/**
 * Charges a request against its route's budget — exactly once.
 *
 * The route names a policy; nothing else in the codebase calls `consume`. In the
 * version this replaces the middleware and the handler both charged the same
 * budget, so every attempt cost two and the effective limit was silently half
 * the configured one.
 */
export const rateLimitPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { services } = options;

    app.addHook('preHandler', async (request, reply) => {
      const policy = request.routeOptions.config.rateLimit;

      if (policy === undefined) {
        return;
      }

      const decision = await services.rateLimiter.enforce(policy, {
        ip: request.context.clientIp,
        // A signed-in caller is keyed by account. On sign-in nobody is
        // authenticated yet, so the submitted identifier stands in — which is
        // what makes "ten attempts against one account" a limit rather than
        // "ten attempts per address, from as many addresses as you like".
        identity: request.auth.userId ?? identifierFromBody(request.body),
      });

      void reply.headers(RateLimiter.headers(decision));
    });
  },
  { name: 'rate-limit', dependencies: ['authentication'] },
);

/**
 * The account a sign-in attempt is aimed at.
 *
 * Lower-cased so `Kitsune` and `kitsune` draw from one budget; an attacker who
 * could split them by changing the case would get a fresh allowance per spelling.
 */
function identifierFromBody(body: unknown): string | null {
  if (typeof body !== 'object' || body === null || !('identifier' in body)) {
    return null;
  }

  const { identifier } = body;

  return typeof identifier === 'string' ? identifier.trim().toLowerCase().slice(0, 254) : null;
}
