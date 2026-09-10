import type { FastifyInstance, RouteOptions } from 'fastify';

/**
 * URL prefixes owned by a framework plugin rather than by this application.
 *
 * The documentation UI registers its own asset routes, which cannot carry an
 * access declaration because we do not write them. Listing them explicitly is
 * the point: an unrecognised route without a declaration fails startup instead
 * of quietly becoming public.
 */
const FOREIGN_PREFIXES: readonly string[] = ['/docs'];

/**
 * Fails startup if any route reached the server without an access rule.
 *
 * `defineRoute` already makes `access` a required field, so a route declared the
 * normal way cannot be missing one — this catches the other door: someone
 * calling `app.get('/admin/users', handler)` directly, bypassing the router
 * entirely. That is precisely how the site this replaces ended up with an
 * unprotected admin area, and it is the kind of mistake that reviews miss
 * because the diff looks like a perfectly ordinary route.
 *
 * Boot-time, not request-time: an endpoint that is only discovered to be
 * unguarded when someone requests it has already been unguarded in production.
 */
export function auditRouteDeclarations(app: FastifyInstance): void {
  app.addHook('onRoute', (route: RouteOptions) => {
    if (route.config?.access !== undefined) {
      return;
    }

    // HEAD is synthesised by Fastify for every GET and inherits its config;
    // OPTIONS comes from the CORS plugin and never reaches a handler.
    if (route.method === 'HEAD' || route.method === 'OPTIONS') {
      return;
    }

    if (FOREIGN_PREFIXES.some((prefix) => route.url.startsWith(prefix))) {
      return;
    }

    throw new Error(
      `Route ${String(route.method)} ${route.url} was registered without an access declaration. ` +
        'Declare it with defineRoute() so its access rule is stated next to its handler.',
    );
  });
}
