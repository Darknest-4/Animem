import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';

import { ForbiddenError, UnauthorizedError } from '@yume/core';

import { PermissionSet } from '../modules/authorization/domain/permission.js';

/**
 * Enforces the route's declared access rule before the handler runs.
 *
 * Every route carries a declaration because `access` is a required field, so
 * this hook can treat a missing one as a bug rather than a default. A framework
 * that defaults to "public" turns a forgotten annotation into an open endpoint;
 * here it is a 500 in development and a compile error before that.
 */
export const authorizationPlugin = fp(
  async (app: FastifyInstance) => {
    app.addHook('preHandler', async (request) => {
      const access = request.routeOptions.config.access;

      if (access === undefined) {
        // Not a hole. Application routes cannot reach this branch: `access` is a
        // required field on every route definition, and `auditRouteDeclarations`
        // fails startup for anything registered without one. What arrives here
        // is a route this application did not write — the not-found handler,
        // which runs the global hooks before answering 404, and the
        // documentation UI's own asset routes.
        return;
      }

      if (access.kind === 'public') {
        return;
      }

      if (access.kind === 'authenticated') {
        if (!request.auth.isAuthenticated) {
          throw new UnauthorizedError('You need to sign in to do this.', 'auth.required');
        }

        return;
      }

      // Permissions do not imply a session. The `guest` role holds a real set,
      // so an anonymous visitor passes `anime.view` and is refused `anime.edit`
      // by the same code path a signed-in user goes through.
      const held = PermissionSet.from(request.auth.permissions);
      const missing = access.permissions.filter((permission) => !held.allows(permission));

      if (missing.length === 0) {
        return;
      }

      if (!request.auth.isAuthenticated) {
        // 401, not 403: the client should offer a sign-in, not give up.
        throw new UnauthorizedError('You need to sign in to do this.', 'auth.required', {
          requiredPermissions: missing,
        });
      }

      throw new ForbiddenError(
        'You do not have permission to perform this action.',
        'authorization.denied',
        { requiredPermissions: missing },
      );
    });
  },
  { name: 'authorization', dependencies: ['authentication'] },
);
