import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';

import { uuidV7 } from '@yume/core';

import type { Services } from '../container/services.js';
import { ANONYMOUS } from '../http/context.js';

const REQUEST_ID_HEADER = 'x-request-id';

/**
 * Establishes the per-request facts everything downstream reads.
 *
 * Runs first, so no later hook has to re-derive the client address — and, more
 * to the point, so no two of them can derive it differently. The site this
 * replaces read `HTTP_CLIENT_IP` in one file and `REMOTE_ADDR` in another, which
 * meant a ban applied to one address and a rate limit to another.
 */
export const requestContextPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { services } = options;

    // Declared up front, without a value: Fastify then reserves the slot on the
    // request prototype so the shape stays monomorphic. Adding a property to an
    // existing request object inside a hook deoptimises every request that
    // follows, and a shared default value would be the same object on all of them.
    app.decorateRequest('context');
    app.decorateRequest('auth');

    app.addHook('onRequest', async (request, reply) => {
      const inbound = request.headers[REQUEST_ID_HEADER];
      // An id supplied by the edge is honoured so one identifier follows a
      // request across Caddy, nginx and the API. It is length-capped because it
      // is echoed into responses and written to logs.
      const requestId =
        typeof inbound === 'string' && inbound.length > 0 && inbound.length <= 64
          ? inbound
          : uuidV7();

      request.context = {
        requestId,
        clientIp: services.trustedProxies.resolve(
          request.socket.remoteAddress,
          request.headers as Record<string, string | undefined>,
        ),
        userAgent: (request.headers['user-agent'] ?? '').slice(0, 512),
        startedAt: performance.now(),
      };
      request.auth = ANONYMOUS;

      void reply.header(REQUEST_ID_HEADER, requestId);
    });

    app.addHook('onResponse', async (request, reply) => {
      services.logger.info('request', {
        requestId: request.context.requestId,
        method: request.method,
        path: request.url,
        status: reply.statusCode,
        durationMs: Math.round(performance.now() - request.context.startedAt),
        ip: request.context.clientIp,
        userId: request.auth.userId,
      });
    });
  },
  { name: 'request-context' },
);
