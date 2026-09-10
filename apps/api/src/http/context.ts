import type { Access } from './access.js';

/**
 * Who the caller is, resolved once per request.
 *
 * Attached by the authentication plugin and read by everything downstream, so
 * no handler ever re-derives it — and no handler can accidentally trust a
 * different source than the authorization check did.
 */
export interface AuthContext {
  readonly userId: string | null;
  readonly sessionId: string | null;
  readonly roles: readonly string[];
  readonly permissions: readonly string[];
  readonly isAuthenticated: boolean;
}

export const ANONYMOUS: AuthContext = Object.freeze({
  userId: null,
  sessionId: null,
  roles: Object.freeze(['guest']),
  permissions: Object.freeze([]),
  isAuthenticated: false,
});

/** Everything the request-scoped middleware chain establishes. */
export interface RequestContext {
  readonly requestId: string;
  /** Resolved through the trusted-proxy chain, never taken from a raw header. */
  readonly clientIp: string;
  readonly userAgent: string;
  readonly startedAt: number;
}

declare module 'fastify' {
  interface FastifyRequest {
    context: RequestContext;
    auth: AuthContext;
    /** The route's own declaration, so plugins can read it without a lookup. */
    routeAccess?: Access;
  }
}
