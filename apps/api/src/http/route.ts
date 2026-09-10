import type { FastifyReply, FastifyRequest, RouteHandlerMethod } from 'fastify';
import type { z, ZodTypeAny } from 'zod';

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

/**
 * The policy attached to a route, as the request pipeline reads it.
 *
 * Carried on Fastify's per-route `config`, so every hook reads the same
 * declaration the route author wrote — no parallel table of URLs to keep in
 * sync, which is precisely how the legacy site ended up with an admin area
 * whose protection lived in a commented-out .htaccess block.
 */
export interface RouteConfig {
  readonly access: Access;
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

type InferOr<TSchema, TFallback> = TSchema extends ZodTypeAny ? z.infer<TSchema> : TFallback;

/** The request shape implied by a route's own schemas. */
export interface RouteGeneric<TSchema extends RouteSchema> {
  Body: InferOr<TSchema['body'], unknown>;
  Params: InferOr<TSchema['params'], unknown>;
  Querystring: InferOr<TSchema['querystring'], unknown>;
}

/**
 * A handler whose request is typed by the route's schemas.
 *
 * This is what makes validation single-sourced: the schema validates the input
 * *and* types it, so a handler never re-parses a body it was just handed. Two
 * descriptions of one payload is how a handler ends up trusting a field the
 * validator does not actually require.
 */
export type TypedHandler<TSchema extends RouteSchema> = (
  request: FastifyRequest<RouteGeneric<TSchema>>,
  reply: FastifyReply,
) => Promise<unknown>;

export interface RouteDefinition extends RouteConfig {
  readonly method: HttpMethod;
  readonly url: string;
  /** Required, so a route with no access rule cannot compile. */
  readonly access: Access;
  readonly schema: RouteSchema;
  readonly handler: RouteHandlerMethod;
}

export interface RouteInput<TSchema extends RouteSchema> extends RouteConfig {
  readonly method: HttpMethod;
  readonly url: string;
  readonly schema: TSchema;
  readonly handler: TypedHandler<TSchema>;
}

/**
 * Declares a route.
 *
 * Two jobs. It infers the handler's request type from the route's own schemas,
 * and it makes `access` a required property of every route object in the
 * codebase — so "somebody forgot the permission check" is a type error rather
 * than an open endpoint.
 *
 * The cast is the one place a typed handler is widened to Fastify's untyped
 * signature. Doing it here, once, is what keeps every route file free of casts:
 * the alternative is each of them asserting its own body shape, which is the
 * same unsound step performed sixty times instead of once, in sixty places
 * nobody reviews as carefully as this line.
 */
export function defineRoute<const TSchema extends RouteSchema>(
  definition: RouteInput<TSchema>,
): RouteDefinition {
  return definition as unknown as RouteDefinition;
}

declare module 'fastify' {
  interface FastifyContextConfig {
    access?: Access;
    rateLimit?: RateLimitPolicyName;
    csrfExempt?: boolean;
    featureFlag?: string;
  }
}
