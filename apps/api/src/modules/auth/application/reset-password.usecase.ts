import { DomainRuleError } from '@yume/core';

import { passwordChangedMessage } from '../../mail/templates/password-changed.template.js';
import { markEmailVerified } from '../../users/domain/user.entity.js';
import { replacePassword } from '../domain/credential.entity.js';
import type { AuthDependencies } from './auth.dependencies.js';

export interface ResetPasswordCommand {
  readonly token: string;
  readonly password: string;
}

export interface ResetPasswordResult {
  /** Other sessions ended by the reset. */
  readonly revokedSessions: number;
}

/**
 * Completes a reset from a mailed link.
 *
 * Every existing session is revoked. If the reset was a recovery from a
 * takeover, leaving the attacker's session alive would make the whole exercise
 * pointless — the new password would protect an account they are already inside.
 */
export class ResetPasswordUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: ResetPasswordCommand): Promise<ResetPasswordResult> {
    const { deps } = this;
    const now = deps.clock.now();
    const { token, user } = await deps.tokenRedeemer.redeem('password_reset', command.token);

    deps.passwordPolicy.assertAcceptable(command.password, [user.username, user.email]);

    const credential = await deps.repositories.credentials.findForUser(user.id);

    if (credential === null) {
      throw new DomainRuleError('This account has no password to reset.', 'auth.no_credential');
    }

    const passwordHash = await deps.hasher.hash(command.password);

    const revokedSessions = await deps.unitOfWork.run(async (repos) => {
      await repos.tokens.markConsumed(token.id, now);
      await repos.credentials.update(replacePassword(credential, passwordHash, now));

      // Reaching the link proves control of the mailbox, which is the same
      // proof email verification asks for. Making them do it twice strands
      // anyone who reset before verifying.
      if (user.emailVerifiedAt === null) {
        await repos.users.update(markEmailVerified(user, now));
      }

      return repos.sessions.revokeAllForUser(user.id, now, 'password_reset');
    });

    deps.logger.info('Password reset completed.', { userId: user.id, revokedSessions });

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
