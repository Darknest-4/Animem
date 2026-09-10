import { DomainRuleError, UnauthorizedError } from '@yume/core';

import { passwordChangedMessage } from '../../mail/templates/password-changed.template.js';
import type { UserId } from '../../users/domain/user.types.js';
import { replacePassword } from '../domain/credential.entity.js';
import { isLegacy } from '../domain/credential.types.js';
import { revokeSession } from '../domain/session.entity.js';
import type { SessionId } from '../domain/session.types.js';
import type { AuthDependencies } from './auth.dependencies.js';

export interface ChangePasswordCommand {
  readonly userId: UserId;
  /** Kept alive, so changing a password does not sign you out of the tab you did it in. */
  readonly currentSessionId: SessionId;
  readonly currentPassword: string;
  readonly newPassword: string;
}

export interface ChangePasswordResult {
  readonly revokedSessions: number;
}

/**
 * A deliberate change by someone already signed in.
 *
 * The current password is re-checked even though the session is valid: a session
 * left open on a shared machine is exactly the case this defends against, and
 * without the check it becomes a one-click account takeover.
 */
export class ChangePasswordUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: ChangePasswordCommand): Promise<ChangePasswordResult> {
    const { deps } = this;
    const now = deps.clock.now();
    const user = await deps.repositories.users.findById(command.userId);

    if (user === null) {
      throw new UnauthorizedError('Your session is no longer valid.', 'auth.session_invalid');
    }

    const credential = await deps.repositories.credentials.findForUser(user.id);

    if (credential === null) {
      throw new DomainRuleError('This account has no password set.', 'auth.no_credential');
    }

    const verified = isLegacy(credential)
      ? deps.hasher.verifyLegacySha256(command.currentPassword, credential.passwordHash)
      : await deps.hasher.verify(command.currentPassword, credential.passwordHash);

    if (!verified) {
      throw new UnauthorizedError(
        'Your current password is not correct.',
        'auth.invalid_current_password',
      );
    }

    deps.passwordPolicy.assertAcceptable(command.newPassword, [user.username, user.email]);

    // The old password was just verified, so comparing the plaintexts answers
    // "did anything actually change?" exactly — and without a second Argon2
    // verify, which would also have to special-case a legacy digest.
    if (command.newPassword === command.currentPassword) {
      throw new DomainRuleError(
        'Your new password must be different from your current one.',
        'auth.password_unchanged',
      );
    }

    const passwordHash = await deps.hasher.hash(command.newPassword);

    const revokedSessions = await deps.unitOfWork.run(async (repos) => {
      await repos.credentials.update(replacePassword(credential, passwordHash, now));

      const sessions = await repos.sessions.findActiveForUser(user.id, now);
      let revoked = 0;

      // Everything except the tab in front of the user. Signing them out of
      // their own browser for changing their password teaches people to avoid
      // changing their password.
      for (const session of sessions) {
        if (session.id === command.currentSessionId) {
          continue;
        }

        await repos.sessions.update(revokeSession(session, now, 'password_changed'));
        revoked += 1;
      }

      return revoked;
    });

    deps.logger.info('Password changed.', { userId: user.id, revokedSessions });

    await deps.mailer.send(
      passwordChangedMessage({
        appName: deps.config.app.name,
        to: user.email,
        username: user.username,
        changedAt: now,
        supportUrl: new URL('/support', deps.config.app.webUrl).toString(),
      }),
    );

    return { revokedSessions };
  }
}
