import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';

import { ForbiddenError } from '@yume/core';

import type { Services } from '../container/services.js';

const CSRF_HEADER = 'x-csrf-token';
const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);

/**
 * Double-submit CSRF verification for cookie-authenticated writes.
 *
 * Scoped precisely: only unsafe methods, only when the credential arrived as a
 * cookie, and only when the route has not declared itself exempt. A bearer token
 * has to be attached by script, and a cross-site page cannot read one — so
 * demanding a CSRF token there would break every API client to defend against an
 * attack that cannot happen.
 *
 * The token is HMAC'd against the session id, so one minted for a different
 * session does not verify.
 */
export const csrfPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { services } = options;

    app.addHook('preHandler', async (request) => {
      if (SAFE_METHODS.has(request.method)) {
        return;
      }

      if (request.routeOptions.config.csrfExempt === true) {
        return;
      }

      const sessionId = request.auth.sessionId;

      if (sessionId === null) {
        // Nothing to ride: no session cookie means no ambient authority for a
        // cross-site form to borrow.
        return;
      }

      if (services.sessionCookie.read(request)?.source !== 'cookie') {
        return;
      }

      const presented = request.headers[CSRF_HEADER];

      if (!services.csrf.isValid(sessionId, typeof presented === 'string' ? presented : null)) {
        services.logger.warn('CSRF verification failed.', {
          requestId: request.context.requestId,
          path: request.url,
          userId: request.auth.userId,
          hadToken: typeof presented === 'string',
        });

        throw new ForbiddenError(
          'This request could not be verified. Reload the page and try again.',
          'csrf.invalid',
        );
      }
    });
  },
  { name: 'csrf', dependencies: ['authentication'] },
);
