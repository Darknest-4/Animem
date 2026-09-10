import type { FastifyInstance, RouteOptions } from 'fastify';

import type { RouteDefinition } from './route.js';

/**
 * Turns route declarations into Fastify routes.
 *
 * The access rule, rate-limit budget, CSRF exemption and feature gate travel on
 * the route's `config`, where the global hooks read them. That indirection is
 * what makes the guarantee hold: there is one pipeline, it reads the
 * declaration the author wrote next to the handler, and a route cannot opt out
 * of it by forgetting to add a middleware to a list.
 */
export function registerRoutes(app: FastifyInstance, routes: readonly RouteDefinition[]): void {
  for (const route of routes) {
    const options: RouteOptions = {
      method: route.method,
      url: route.url,
      handler: route.handler,
      config: {
        access: route.access,
        ...(route.rateLimit === undefined ? {} : { rateLimit: route.rateLimit }),
        ...(route.csrfExempt === undefined ? {} : { csrfExempt: route.csrfExempt }),
        ...(route.featureFlag === undefined ? {} : { featureFlag: route.featureFlag }),
      },
      schema: {
        ...(route.schema.summary === undefined ? {} : { summary: route.schema.summary }),
        ...(route.schema.description === undefined ? {} : { description: route.schema.description }),
        ...(route.schema.tags === undefined ? {} : { tags: [...route.schema.tags] }),
        ...(route.schema.params === undefined ? {} : { params: route.schema.params }),
        ...(route.schema.querystring === undefined ? {} : { querystring: route.schema.querystring }),
        ...(route.schema.body === undefined ? {} : { body: route.schema.body }),
        ...(route.schema.response === undefined ? {} : { response: { ...route.schema.response } }),
      },
    };

    app.route(options);
  }
}
