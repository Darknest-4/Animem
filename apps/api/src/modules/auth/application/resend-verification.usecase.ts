import type { AuthDependencies } from './auth.dependencies.js';

export interface ResendVerificationCommand {
  readonly email: string;
  readonly ip: string;
}

/**
 * Issues a fresh verification link.
 *
 * Returns nothing and never reports whether the address exists or is already
 * verified. The endpoint is unauthenticated, so any distinguishable response
 * turns it into a registration oracle: submit an address, read the answer, learn
 * whether that person has an account here.
 */
export class ResendVerificationUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: ResendVerificationCommand): Promise<void> {
    const user = await this.deps.repositories.users.findByEmail(command.email);

    if (user === null) {
      this.deps.logger.debug('Verification resend ignored.', { reason: 'unknown_address' });

      return;
    }

    if (user.emailVerifiedAt !== null) {
      this.deps.logger.debug('Verification resend ignored.', { reason: 'already_verified' });

      return;
    }

    await this.deps.tokenIssuer.issueEmailVerification(user, command.ip);
  }
}
