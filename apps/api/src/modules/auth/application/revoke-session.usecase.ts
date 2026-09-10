import { NotFoundError } from '@yume/core';

import type { UserId } from '../../users/domain/user.types.js';
import { revokeSession } from '../domain/session.entity.js';
import type { SessionId } from '../domain/session.types.js';
import type { AuthDependencies } from './auth.dependencies.js';

export interface RevokeSessionCommand {
  readonly actorId: UserId;
  readonly sessionId: SessionId;
}

export class RevokeSessionUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: RevokeSessionCommand): Promise<void> {
    const { repositories: repos } = this.deps;
    const session = await repos.sessions.findById(command.sessionId);

    // Ownership is checked before existence is admitted. Returning 403 for
    // "someone else's session" and 404 for "no such session" would turn this
    // endpoint into a way to enumerate valid session ids.
    if (session?.userId !== command.actorId) {
      throw new NotFoundError('session');
    }

    await repos.sessions.update(
      revokeSession(session, this.deps.clock.now(), 'revoked_by_user'),
    );

    this.deps.logger.info('Session revoked by its owner.', {
      userId: command.actorId,
      sessionId: command.sessionId,
    });
  }
}
