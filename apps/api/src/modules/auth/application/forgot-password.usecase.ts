import type { AuthDependencies } from './auth.dependencies.js';

export interface ForgotPasswordCommand {
  readonly email: string;
  readonly ip: string;
}

/**
 * Starts a password reset.
 *
 * Like resending verification, this reports success unconditionally: the caller
 * is anonymous, and telling them whether an address is registered is an account
 * enumeration hole regardless of how carefully the rest of the flow is written.
 *
 * A suspended account is deliberately included. Locking someone out of the reset
 * flow does not stop the suspension being lifted later, and it does leak the
 * account's state to anyone who tries.
 */
export class ForgotPasswordUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: ForgotPasswordCommand): Promise<void> {
    const user = await this.deps.repositories.users.findByEmail(command.email);

    if (user === null) {
      this.deps.logger.debug('Password reset requested for an unknown address.', {
        ip: command.ip,
      });

      return;
    }

    await this.deps.tokenIssuer.issuePasswordReset(user, command.ip);
  }
}
