import type { AuthenticatedUser } from '@yume/contracts';

import { markEmailVerified } from '../../users/domain/user.entity.js';
import type { AuthDependencies } from './auth.dependencies.js';
import { toAuthenticatedUser } from './authenticated-user.view.js';

export interface VerifyEmailCommand {
  readonly token: string;
}

/**
 * Redeems a verification link.
 *
 * Consuming the token and activating the account happen in one transaction: a
 * token marked used against an account that never activated leaves a user who
 * cannot verify and cannot re-request, because the link they were sent is now
 * spent.
 */
export class VerifyEmailUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: VerifyEmailCommand): Promise<AuthenticatedUser> {
    const { deps } = this;
    const now = deps.clock.now();
    const { token, user } = await deps.tokenRedeemer.redeem('email_verification', command.token);
    const verified = markEmailVerified(user, now);

    await deps.unitOfWork.run(async (repos) => {
      await repos.tokens.markConsumed(token.id, now);
      await repos.users.update(verified);
    });

    deps.logger.info('Email address verified.', { userId: user.id });

    const permissions = await deps.repositories.roles.permissionsForUser(user.id);

    return toAuthenticatedUser(verified, permissions.toArray());
  }
}
