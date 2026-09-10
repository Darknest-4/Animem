import { addSeconds, defineId, Duration, type IdOf } from '@yume/core';

import type { UserId } from '../../users/domain/user.types.js';

export const OneTimeTokenId = defineId<'OneTimeTokenId'>('token');
export type OneTimeTokenId = IdOf<typeof OneTimeTokenId>;

export const TOKEN_PURPOSES = ['email_verification', 'password_reset'] as const;
export type TokenPurpose = (typeof TOKEN_PURPOSES)[number];

/**
 * How long a freshly issued link stays usable.
 *
 * Verification is generous — people open confirmation mail the next morning.
 * Reset is deliberately short: a reset link is a full account takeover if it
 * leaks from a mailbox, a browser history or a shared screen.
 */
export const TOKEN_LIFETIME_SECONDS: Readonly<Record<TokenPurpose, number>> = Object.freeze({
  email_verification: Duration.hours(24),
  password_reset: Duration.hours(1),
});

export interface OneTimeToken {
  readonly id: OneTimeTokenId;
  readonly userId: UserId;
  readonly purpose: TokenPurpose;
  readonly tokenHash: string;
  readonly requestedIp: string | null;
  readonly createdAt: Date;
  readonly expiresAt: Date;
  /** Recorded rather than deleted, so a replay is distinguishable from an expiry. */
  readonly consumedAt: Date | null;
}

export function issueToken(
  userId: UserId,
  purpose: TokenPurpose,
  tokenHash: string,
  now: Date,
  requestedIp: string | null = null,
): OneTimeToken {
  return {
    id: OneTimeTokenId.generate(),
    userId,
    purpose,
    tokenHash,
    requestedIp,
    createdAt: now,
    expiresAt: addSeconds(now, TOKEN_LIFETIME_SECONDS[purpose]),
    consumedAt: null,
  };
}

export function isTokenUsable(token: OneTimeToken, now: Date): boolean {
  return token.consumedAt === null && token.expiresAt.getTime() > now.getTime();
}

export function consumeToken(token: OneTimeToken, now: Date): OneTimeToken {
  return { ...token, consumedAt: now };
}
