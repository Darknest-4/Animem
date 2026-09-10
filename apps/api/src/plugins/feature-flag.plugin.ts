import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';

import { NotFoundError } from '@yume/core';

import type { Services } from '../container/services.js';

/**
 * Hides a route while its flag is off.
 *
 * 404, not 403: a dark-launched endpoint should be indistinguishable from one
 * that does not exist, or the flag advertises exactly what is coming and to
 * whom. This is the mechanism the legacy codebase lacked — it carried four
 * unfinished rewrites gated by commented-out blocks and a hardcoded IP address,
 * all of them permanently half-live because there was no way to ship one dark.
 */
export const featureFlagPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { services } = options;

    app.addHook('preHandler', async (request) => {
      const flag = request.routeOptions.config.featureFlag;

      if (flag === undefined) {
        return;
      }

      const enabled = await services.featureFlags.isEnabled(flag, {
        userId: request.auth.userId,
        roles: request.auth.roles,
        ip: request.context.clientIp,
      });

      if (!enabled) {
        throw new NotFoundError('route');
      }
    });
  },
  { name: 'feature-flag', dependencies: ['authentication'] },
);
