import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';

import type { Services } from '../container/services.js';
import { ANONYMOUS } from '../http/context.js';

/**
 * Resolves the caller's identity, once, for every request.
 *
 * It never rejects. A missing, expired or forged token simply leaves the request
 * anonymous, and whether anonymous is acceptable is the route's declared
 * `access` to decide. Rejecting here would mean a stale cookie turns the public
 * catalogue into a 401 — which is exactly what the previous iteration of this
 * codebase did, because its `->can()` helper forced authentication before it
 * checked the permission, and the `guest` role's `anime.view` grant was
 * therefore unreachable.
 */
export const authenticationPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { services } = options;

    app.addHook('onRequest', async (request, reply) => {
      const presented = services.sessionCookie.read(request);

      if (presented === null) {
        request.auth = ANONYMOUS;

        return;
      }

      const authenticated = await services.auth.authenticator.authenticate(
        presented.token,
        request.context.clientIp,
        request.context.userAgent,
      );

      if (authenticated === null) {
        request.auth = ANONYMOUS;

        // A cookie that no longer resolves is dead weight the browser would
        // keep sending on every request; clearing it also stops a revoked
        // session from looking signed-in in the UI.
        if (presented.source === 'cookie') {
          services.sessionCookie.clear(reply);
        }

        return;
      }

      request.auth = {
        userId: authenticated.user.id,
        sessionId: authenticated.session.id,
        roles: authenticated.user.roles,
        permissions: authenticated.permissions.toArray(),
        isAuthenticated: true,
      };

      // Rotation happens inside the service; putting the replacement back on the
      // wire is this layer's job, because the service knows nothing about
      // cookies. A bearer client reads its replacement from the header.
      if (authenticated.rotatedToken !== null) {
        if (presented.source === 'cookie') {
          services.sessionCookie.set(reply, authenticated.rotatedToken, authenticated.session.expiresAt);
        } else {
          void reply.header('x-session-token', authenticated.rotatedToken);
        }
      }
    });
  },
  { name: 'authentication', dependencies: ['request-context'] },
);
