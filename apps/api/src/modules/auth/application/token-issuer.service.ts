import type { Config } from '@yume/config';
import type { Clock } from '@yume/core';
import type { Logger } from '@yume/logger';
import type { TokenGenerator } from '@yume/security';

import type { Mailer } from '../../mail/domain/mailer.js';
import { resetPasswordMessage } from '../../mail/templates/reset-password.template.js';
import { verifyEmailMessage } from '../../mail/templates/verify-email.template.js';
import type { User } from '../../users/domain/user.types.js';
import type { OneTimeTokenRepository } from '../domain/auth.repository.js';
import {
  issueToken,
  TOKEN_LIFETIME_SECONDS,
  type TokenPurpose,
} from '../domain/one-time-token.types.js';

export interface TokenIssuerDependencies {
  readonly tokens: OneTimeTokenRepository;
  readonly mailer: Mailer;
  readonly tokenGenerator: TokenGenerator;
  readonly clock: Clock;
  readonly config: Config;
  readonly logger: Logger;
}

/**
 * Mints a one-time link and mails it.
 *
 * Registration, "resend verification" and "forgot password" all need the same
 * five steps — invalidate what is outstanding, generate, store the digest, build
 * the URL, send — and this is where they are written once. Three copies is how
 * one of them ends up skipping the invalidation step and leaving two live reset
 * links in two different inboxes.
 */
export class TokenIssuer {
  constructor(private readonly deps: TokenIssuerDependencies) {}

  async issueEmailVerification(user: User, requestedIp: string): Promise<void> {
    const token = await this.mint(user, 'email_verification', requestedIp);

    await this.deps.mailer.send(
      verifyEmailMessage({
        appName: this.deps.config.app.name,
        to: user.email,
        username: user.username,
        verifyUrl: this.buildUrl('/verify-email', token),
        expiresInSeconds: TOKEN_LIFETIME_SECONDS.email_verification,
      }),
    );
  }

  async issuePasswordReset(user: User, requestedIp: string): Promise<void> {
    const token = await this.mint(user, 'password_reset', requestedIp);

    await this.deps.mailer.send(
      resetPasswordMessage({
        appName: this.deps.config.app.name,
        to: user.email,
        username: user.username,
        resetUrl: this.buildUrl('/reset-password', token),
        expiresInSeconds: TOKEN_LIFETIME_SECONDS.password_reset,
        requestedFromIp: requestedIp,
      }),
    );
  }

  /**
   * Invalidates outstanding links of this purpose, then stores a new digest.
   *
   * Superseding rather than accumulating means a second "reset my password"
   * click kills the first link. Without it, an attacker who triggers a reset
   * gets a live token that stays valid for its full hour even after the real
   * owner requests their own.
   */
  private async mint(user: User, purpose: TokenPurpose, requestedIp: string): Promise<string> {
    const now = this.deps.clock.now();
    const superseded = await this.deps.tokens.consumeAllForUser(user.id, purpose, now);
    const generated = this.deps.tokenGenerator.generate();

    await this.deps.tokens.insert(
      issueToken(user.id, purpose, generated.hash, now, requestedIp),
    );

    this.deps.logger.info('One-time token issued.', {
      userId: user.id,
      purpose,
      superseded,
    });

    return generated.plain;
  }

  /**
   * The link points at the web app, not the API.
   *
   * The recipient lands on a page that can show an error or a password form;
   * pointing at the API would put a raw JSON body in front of them.
   */
  private buildUrl(path: string, token: string): string {
    const url = new URL(path, this.deps.config.app.webUrl);

    url.searchParams.set('token', token);

    return url.toString();
  }
}
