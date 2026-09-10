import type { FastifyReply, FastifyRequest, RouteHandlerMethod } from 'fastify';
import type { ZodTypeAny } from 'zod';

import type { Access } from './access.js';

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

/** The rate-limit budget a route draws from. Names are defined in one place. */
export type RateLimitPolicyName =
  | 'auth.login'
  | 'auth.register'
  | 'auth.emailDispatch'
  | 'auth.tokenRedeem'
  | 'auth.passwordChange'
  | 'api.read'
  | 'api.write';

export interface RouteSchema {
  readonly summary?: string;
  readonly description?: string;
  readonly tags?: readonly string[];
  readonly params?: ZodTypeAny;
  readonly querystring?: ZodTypeAny;
  readonly body?: ZodTypeAny;
  readonly response?: Readonly<Record<number, ZodTypeAny>>;
}

export interface RouteDefinition {
  readonly method: HttpMethod;
  readonly url: string;
  /** Required, so a route with no access rule cannot compile. */
  readonly access: Access;
  readonly schema: RouteSchema;
  readonly handler: RouteHandlerMethod;
  readonly rateLimit?: RateLimitPolicyName;
  /**
   * Skips CSRF verification.
   *
   * Only legitimate where no cookie session exists yet — sign-in, registration
   * and mailed-token redemption. A cross-site form post cannot carry a bearer
   * token or a token from someone's inbox, so there is nothing to ride.
   */
  readonly csrfExempt?: boolean;
  /** Gates the route behind a feature flag; 404 when the flag is off. */
  readonly featureFlag?: string;
}

/**
 * Declares a route.
 *
 * A thin identity function, and that is the point: it exists so `access` is a
 * required property of every route object in the codebase, which turns
 * "somebody forgot the permission check" into a type error rather than an open
 * endpoint.
 */
export function defineRoute(definition: RouteDefinition): RouteDefinition {
  return definition;
}

/** Convenience alias for handlers that need the typed request and reply. */
export type Handler<TRequest extends FastifyRequest = FastifyRequest> = (
  request: TRequest,
  reply: FastifyReply,
) => Promise<unknown>;
