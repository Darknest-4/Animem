import { revokeSession } from '../domain/session.entity.js';
import type { SessionId } from '../domain/session.types.js';
import type { AuthDependencies } from './auth.dependencies.js';

export interface LogoutCommand {
  readonly sessionId: SessionId;
  /** Ends every other session too — the "sign out everywhere" button. */
  readonly everywhere: boolean;
}

export interface LogoutResult {
  /** Sessions ended, including the current one. */
  readonly revoked: number;
}

/**
 * Ends a session server-side.
 *
 * Clearing the cookie is not signing out: a token already copied off the wire
 * keeps working until it expires. Revoking the row is what actually ends it, and
 * the cookie is cleared as a courtesy afterwards.
 */
export class LogoutUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: LogoutCommand): Promise<LogoutResult> {
    const { repositories: repos } = this.deps;
    const now = this.deps.clock.now();
    const session = await repos.sessions.findById(command.sessionId);

    if (session === null) {
      return { revoked: 0 };
    }

    if (command.everywhere) {
      const revoked = await repos.sessions.revokeAllForUser(session.userId, now, 'signed_out_everywhere');

      this.deps.logger.info('Signed out of every session.', {
        userId: session.userId,
        revoked,
      });

      return { revoked };
    }

    await repos.sessions.update(revokeSession(session, now, 'signed_out'));

    return { revoked: 1 };
  }
}
