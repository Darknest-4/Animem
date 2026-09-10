import fp from 'fastify-plugin';
import type { FastifyInstance } from 'fastify';
import { hasZodFastifySchemaValidationErrors } from 'fastify-type-provider-zod';

import { isAppError, ValidationError } from '@yume/core';

import type { Services } from '../container/services.js';
import { requestIdOf } from '../http/context.js';
import { PROBLEM_CONTENT_TYPE, problem, problemHeaders, toProblem } from '../http/problem.js';

/**
 * The single exit for every failure.
 *
 * One response shape for every error the API can produce. The site this replaces
 * returned a JSON object, a bare string or an HTML stack trace depending on
 * which file happened to handle the request, so no client could tell success
 * from failure without guessing.
 */
export const errorHandlerPlugin = fp(
  async (app: FastifyInstance, options: { services: Services }) => {
    const { services } = options;

    app.setErrorHandler((error, request, reply) => {
      const requestId = requestIdOf(request);

      // Schema rejections become field-level 422s rather than Fastify's default
      // 400 with a message about a JSON pointer, which no form can render.
      if (hasZodFastifySchemaValidationErrors(error)) {
        const issues: Record<string, string[]> = {};

        for (const issue of error.validation) {
          const field = issue.instancePath.replace(/^\//u, '').replace(/\//gu, '.') || '_';

          (issues[field] ??= []).push(issue.message);
        }

        const validation = new ValidationError(issues);

        void reply
          .status(validation.status)
          .type(PROBLEM_CONTENT_TYPE)
          .send(
            problem(
              validation.status,
              validation.code,
              validation.message,
              validation.details,
              requestId,
            ),
          );

        return;
      }

      if (isAppError(error)) {
        // Expected: the caller did something the domain refuses. Logged at warn
        // so a flood of 401s is visible without burying real faults.
        services.logger.warn('Request rejected.', {
          requestId,
          code: error.code,
          status: error.status,
          path: request.url,
        });
      } else {
        services.logger.error('Unhandled error.', {
          requestId,
          path: request.url,
          method: request.method,
          error: error instanceof Error ? error.message : String(error),
          stack: error instanceof Error ? error.stack : undefined,
        });
      }

      const body = toProblem(error, {
        ...(requestId === undefined ? {} : { requestId }),
        debug: services.config.debug,
      });

      void reply
        .status(body.status)
        .type(PROBLEM_CONTENT_TYPE)
        .headers(problemHeaders(error))
        .send(body);
    });

    app.setNotFoundHandler((request, reply) => {
      void reply
        .status(404)
        .type(PROBLEM_CONTENT_TYPE)
        .send(
          problem(
            404,
            'route.not_found',
            `No route matches ${request.method} ${request.url}.`,
            {},
            requestIdOf(request),
          ),
        );
    });
  },
  { name: 'error-handler' },
);
