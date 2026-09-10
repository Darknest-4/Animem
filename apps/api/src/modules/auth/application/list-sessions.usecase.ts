import type { Session as SessionView } from '@yume/contracts';

import type { UserId } from '../../users/domain/user.types.js';
import type { SessionId } from '../domain/session.types.js';
import type { AuthDependencies } from './auth.dependencies.js';

export interface ListSessionsQuery {
  readonly userId: UserId;
  /** Flagged in the response so the UI can label it and refuse to revoke it blindly. */
  readonly currentSessionId: SessionId;
}

/**
 * The "where am I signed in" list.
 *
 * Exists so a user can discover and end a session they do not recognise without
 * needing an administrator — the only practical self-service defence against a
 * token that leaked from a shared machine.
 */
export class ListSessionsUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(query: ListSessionsQuery): Promise<readonly SessionView[]> {
    const now = this.deps.clock.now();
    const sessions = await this.deps.repositories.sessions.findActiveForUser(query.userId, now);

    return sessions.map((session) => ({
      id: session.id,
      created_ip: session.createdIp,
      created_user_agent: session.createdUserAgent,
      created_at: session.createdAt.toISOString(),
      last_seen_at: session.lastSeenAt.toISOString(),
      expires_at: session.expiresAt.toISOString(),
      is_current: session.id === query.currentSessionId,
    }));
  }
}
