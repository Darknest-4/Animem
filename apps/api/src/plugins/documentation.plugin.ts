import swagger from '@fastify/swagger';
import swaggerUi from '@fastify/swagger-ui';
import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';
import { jsonSchemaTransform } from 'fastify-type-provider-zod';

import type { Services } from '../container/services.js';

/**
 * OpenAPI, generated from the same Zod schemas the routes validate with.
 *
 * Generated rather than written, so the documentation cannot drift from the
 * implementation: a renamed field changes the contract, the validator and the
 * spec in one edit. Hand-maintained API docs are wrong within a month, and
 * confidently wrong documentation is worse than none.
 */
export const documentationPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { config } = options.services;

    // Not exposed in production. The spec is a complete map of every endpoint,
    // its parameters and its permission requirements.
    if (config.isProduction) {
      return;
    }

    await app.register(swagger, {
      openapi: {
        info: {
          title: `${config.app.name} API`,
          version: config.app.version,
          description:
            'Every route declares its own access rule, rate-limit budget and feature gate. ' +
            'Errors are RFC 9457 problem documents.',
        },
        servers: [{ url: config.app.url }],
        components: {
          securitySchemes: {
            sessionCookie: { type: 'apiKey', in: 'cookie', name: config.session.cookieName },
            bearer: { type: 'http', scheme: 'bearer' },
          },
        },
      },
      transform: jsonSchemaTransform,
    });

    await app.register(swaggerUi, {
      routePrefix: '/docs',
      uiConfig: { docExpansion: 'list', deepLinking: true },
    });
  },
  { name: 'documentation' },
);
