import { TokenGenerator } from '@yume/security';

import type { PermissionSet } from '../../authorization/domain/permission.js';
import { canAuthenticate, type User } from '../../users/domain/user.types.js';
import { revokeSession, rotateSessionToken, touchSession } from '../domain/session.entity.js';
import { isActive, looksHijacked, shouldRotate, type Session } from '../domain/session.types.js';
import type { AuthDependencies } from './auth.dependencies.js';

export interface AuthenticatedSession {
  readonly user: User;
  readonly session: Session;
  readonly permissions: PermissionSet;
  /**
   * A replacement token when this request rotated it, otherwise null.
   *
   * The HTTP layer is responsible for putting it back on the wire; the service
   * cannot, because it does not know about cookies.
   */
  readonly rotatedToken: string | null;
}

/**
 * Turns a presented token into an identity.
 *
 * Every failure mode returns null rather than throwing: an expired or forged
 * token means the caller is anonymous, and whether anonymous is acceptable is
 * the route's declaration to make, not this service's. Throwing here would turn
 * a stale cookie on a public page into a 401.
 */
export class SessionAuthenticator {
  constructor(private readonly deps: AuthDependencies) {}

  async authenticate(
    presentedToken: string,
    ip: string,
    userAgent: string,
  ): Promise<AuthenticatedSession | null> {
    const { repositories: repos, logger } = this.deps;
    const now = this.deps.clock.now();

    // Looked up by digest, so the token itself is never compared in the
    // database and a dump of the table cannot be replayed.
    const found = await repos.sessions.findByTokenHash(TokenGenerator.hash(presentedToken));

    if (found === null || !isActive(found, now)) {
      return null;
    }

    if (looksHijacked(found, userAgent)) {
      // The whole point of binding is that a stolen cookie replayed from another
      // client kills the session rather than serving it.
      await repos.sessions.update(revokeSession(found, now, 'user_agent_changed'));
      logger.warn('Session revoked: client fingerprint changed.', {
        sessionId: found.id,
        userId: found.userId,
        ip,
      });

      return null;
    }

    const user = await repos.users.findById(found.userId);

    if (user === null || !canAuthenticate(user)) {
      // Suspension takes effect on the next request rather than at the next
      // sign-in, which is the only version of "suspended" that means anything.
      if (user !== null) {
        await repos.sessions.update(revokeSession(found, now, 'account_not_active'));
      }

      return null;
    }

    const { session, rotatedToken } = await this.refresh(found, now);
    const permissions = await repos.roles.permissionsForUser(user.id);

    return { user, session, permissions, rotatedToken };
  }

  /**
   * Slides the expiry and periodically replaces the token.
   *
   * Rotation is what limits the value of a token captured from a log, a proxy or
   * a shared machine: it stops working at the next rotation interval even if
   * nobody ever notices it was taken.
   */
  private async refresh(
    session: Session,
    now: Date,
  ): Promise<{ session: Session; rotatedToken: string | null }> {
    const { repositories: repos, config } = this.deps;

    if (!shouldRotate(session, config.session, now)) {
      const touched = touchSession(session, config.session, now);

      await repos.sessions.update(touched);

      return { session: touched, rotatedToken: null };
    }

    const replacement = this.deps.tokenGenerator.generate();
    const rotated = touchSession(rotateSessionToken(session, replacement.hash), config.session, now);

    await repos.sessions.update(rotated);

    return { session: rotated, rotatedToken: replacement.plain };
  }
}
