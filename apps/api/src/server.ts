import cookie from '@fastify/cookie';
import Fastify, { LogController, type FastifyBaseLogger, type FastifyInstance } from 'fastify';
import { serializerCompiler, validatorCompiler } from 'fastify-type-provider-zod';

import { createPinoInstance } from '@yume/logger';

import type { Services } from './container/services.js';
import { auditRouteDeclarations } from './http/route-audit.js';
import { registerRoutes } from './http/router.js';
import { authenticationPlugin } from './plugins/authentication.plugin.js';
import { authorizationPlugin } from './plugins/authorization.plugin.js';
import { corsPlugin } from './plugins/cors.plugin.js';
import { csrfPlugin } from './plugins/csrf.plugin.js';
import { documentationPlugin } from './plugins/documentation.plugin.js';
import { errorHandlerPlugin } from './plugins/error-handler.plugin.js';
import { featureFlagPlugin } from './plugins/feature-flag.plugin.js';
import { rateLimitPlugin } from './plugins/rate-limit.plugin.js';
import { requestContextPlugin } from './plugins/request-context.plugin.js';
import { securityHeadersPlugin } from './plugins/security-headers.plugin.js';
import { allRoutes } from './routes.js';

/**
 * Builds the HTTP application.
 *
 * Separate from starting it, so tests drive the real pipeline through
 * `app.inject()` — same hooks, same validation, same error handling — without
 * binding a port. A test that bypasses the middleware chain proves the handler
 * works and says nothing about whether the endpoint is protected.
 *
 * Hook order is the security posture, so it is fixed here and nowhere else:
 *
 *   onRequest   context → authentication
 *   preHandler  feature gate → CSRF → rate limit → authorization
 *
 * The gate runs first because a dark endpoint must not even admit it exists.
 * CSRF precedes the rate limiter so a forged cross-site write cannot burn the
 * victim's budget. Authorization runs last so a refused request has still been
 * counted — otherwise probing for permissions is free.
 */
export async function createServer(services: Services): Promise<FastifyInstance> {
  // Fastify and the application share one pino instance, so a request log line
  // and an application log line from the same request carry the same fields.
  // Typed as Fastify's own logger interface so the instance keeps its default
  // generic parameters and stays assignable to a plain FastifyInstance.
  const loggerInstance: FastifyBaseLogger = createPinoInstance({
    level: services.config.observability.logLevel,
    pretty: services.config.observability.logPretty,
    name: `${services.config.app.name}-http`,
    version: services.config.app.version,
  });

  const app = Fastify({
    loggerInstance,
    // Never trusted blindly: the client address is resolved by our own
    // trusted-proxy walk, which drops hops we recognise and takes the first we
    // do not. Fastify's own `trustProxy` would take the leftmost entry, which is
    // whatever the client chose to inject.
    trustProxy: false,
    // Request logging is ours: one line per response, from the request-context
    // plugin, carrying the resolved client address and the caller's identity.
    // Fastify's own pair of lines would say neither, and would say it twice.
    logController: new LogController({ disableRequestLogging: true }),
    requestIdHeader: false,
    bodyLimit: 1024 * 512,
    ajv: { customOptions: { removeAdditional: false } },
  });

  // One source of truth for request shapes: the same Zod schema validates the
  // input, serialises the response and — through defineRoute — types the handler.
  app.setValidatorCompiler(validatorCompiler);
  app.setSerializerCompiler(serializerCompiler);

  // Installed before anything registers a route, so it sees every one of them.
  auditRouteDeclarations(app);

  await app.register(errorHandlerPlugin, { services });
  await app.register(securityHeadersPlugin, { services });
  await app.register(corsPlugin, { services });
  await app.register(cookie, { secret: services.config.security.appSecret });

  await app.register(requestContextPlugin, { services });
  await app.register(authenticationPlugin, { services });

  await app.register(featureFlagPlugin, { services });
  await app.register(csrfPlugin, { services });
  await app.register(rateLimitPlugin, { services });
  await app.register(authorizationPlugin);

  await app.register(documentationPlugin, { services });

  registerRoutes(app, allRoutes(services));

  return app;
}
