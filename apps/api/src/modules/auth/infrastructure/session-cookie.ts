import type { CookieSerializeOptions } from '@fastify/cookie';
import type { FastifyReply, FastifyRequest } from 'fastify';

import type { Config } from '@yume/config';

const BEARER_PREFIX = 'Bearer ';

/**
 * Where the session token lives on the wire.
 *
 * Cookie *and* bearer, deliberately: browsers get an HttpOnly cookie they cannot
 * leak to a script, while a CLI or a mobile client sends a header and skips CSRF
 * entirely. Keeping both readers in one file is what stops the two paths drifting
 * into disagreeing about which one wins.
 */
export class SessionCookie {
  constructor(private readonly config: Config) {}

  /** The cookie takes precedence: a browser's own credential outranks a header it was told to send. */
  read(request: FastifyRequest): { token: string; source: 'cookie' | 'bearer' } | null {
    const fromCookie = request.cookies[this.config.session.cookieName];

    if (typeof fromCookie === 'string' && fromCookie.length > 0) {
      return { token: fromCookie, source: 'cookie' };
    }

    const authorization = request.headers.authorization;

    if (typeof authorization === 'string' && authorization.startsWith(BEARER_PREFIX)) {
      const token = authorization.slice(BEARER_PREFIX.length).trim();

      if (token.length > 0) {
        return { token, source: 'bearer' };
      }
    }

    return null;
  }

  set(reply: FastifyReply, token: string, expiresAt: Date): void {
    reply.setCookie(this.config.session.cookieName, token, this.options(expiresAt));
  }

  clear(reply: FastifyReply): void {
    reply.clearCookie(this.config.session.cookieName, { ...this.options(), maxAge: 0 });
  }

  private options(expiresAt?: Date): CookieSerializeOptions {
    return {
      // Unreadable to script, so an XSS bug cannot exfiltrate the session.
      httpOnly: true,
      // HTTPS only in production; relaxed locally so development works over http.
      secure: this.config.session.cookieSecure,
      // Lax, not Strict: Strict drops the cookie on every inbound link, so a user
      // following a link from anywhere lands signed out. Lax still allows a
      // top-level cross-site POST, which is why CSRF tokens exist alongside it.
      sameSite: 'lax',
      path: '/',
      ...(expiresAt === undefined ? {} : { expires: expiresAt }),
    };
  }
}
