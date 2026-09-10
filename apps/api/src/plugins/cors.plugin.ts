import cors from '@fastify/cors';
import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';

import type { Services } from '../container/services.js';

/**
 * Cross-origin access for the web app.
 *
 * Origins come from configuration and are matched exactly. Reflecting whatever
 * `Origin` arrives — the shape most CORS examples show — while also setting
 * `credentials: true` hands every site on the internet the ability to make
 * authenticated requests with the user's cookie.
 */
export const corsPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { config, logger } = options.services;
    const allowed = new Set(config.security.corsAllowedOrigins);

    await app.register(cors, {
      origin(origin, callback) {
        // No Origin header at all: a same-origin navigation, a server-to-server
        // call or curl. There is nothing to protect and nothing to reflect.
        if (origin === undefined) {
          callback(null, true);

          return;
        }

        if (allowed.has(origin)) {
          callback(null, true);

          return;
        }

        logger.warn('Blocked a cross-origin request.', { origin });
        callback(null, false);
      },
      credentials: true,
      methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
      allowedHeaders: ['content-type', 'authorization', 'x-csrf-token', 'x-request-id'],
      exposedHeaders: [
        'x-request-id',
        'x-session-token',
        'ratelimit-limit',
        'ratelimit-remaining',
        'ratelimit-reset',
        'retry-after',
      ],
      maxAge: 600,
    });
  },
  { name: 'cors' },
);
