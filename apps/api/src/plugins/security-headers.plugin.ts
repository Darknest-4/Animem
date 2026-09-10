import helmet from '@fastify/helmet';
import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';

import type { Services } from '../container/services.js';

/**
 * Response hardening headers.
 *
 * The API serves JSON, so the policy is the restrictive one: nothing may be
 * loaded, framed or embedded. The documentation UI is the single exception and
 * relaxes it for its own prefix only.
 */
export const securityHeadersPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { config } = options.services;

    await app.register(helmet, {
      contentSecurityPolicy: {
        directives: {
          defaultSrc: ["'none'"],
          frameAncestors: ["'none'"],
          baseUri: ["'none'"],
          formAction: ["'none'"],
        },
      },
      // Only over HTTPS, and only when the edge terminates it — sending HSTS
      // from a plain-http development server pins localhost to HTTPS in the
      // developer's browser, which is a memorable afternoon to lose.
      hsts: config.security.hsts
        ? { maxAge: 31_536_000, includeSubDomains: true, preload: false }
        : false,
      crossOriginResourcePolicy: { policy: 'same-site' },
      referrerPolicy: { policy: 'strict-origin-when-cross-origin' },
      // Set explicitly rather than left to the default: a JSON body sniffed as
      // HTML is how a reflected value becomes stored XSS.
      noSniff: true,
      frameguard: { action: 'deny' },
      // Advertising the runtime only helps someone matching it to a CVE list.
      hidePoweredBy: true,
    });
  },
  { name: 'security-headers' },
);
