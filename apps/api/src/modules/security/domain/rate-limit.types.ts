import { Duration } from '@yume/core';

import type { RateLimitPolicyName } from '../../../http/route.js';

/** What the key is built from. */
export type RateLimitScope =
  /** One budget per client address. The only option for anonymous endpoints. */
  | 'ip'
  /** One budget per account, wherever it is used from. */
  | 'identity'
  /** Both, so one noisy address cannot exhaust an account's budget or vice versa. */
  | 'ip+identity';

export interface RateLimitPolicy {
  readonly limit: number;
  readonly windowSeconds: number;
  readonly scope: RateLimitScope;
}

/**
 * Every budget in the system, in one table.
 *
 * Separate budgets per policy is the fix for a specific bug in the site this
 * replaces: five endpoints — sign-in, register, reset request, reset redeem and
 * password change — all drew from one counter, so failing to sign in five times
 * also locked you out of asking for a reset. Sharing a budget across unrelated
 * actions makes a limit that is either useless or hostile, and usually both.
 */
export const RATE_LIMIT_POLICIES: Readonly<Record<RateLimitPolicyName, RateLimitPolicy>> =
  Object.freeze({
    /** Tight, and keyed on both, so guessing one account is not helped by a botnet. */
    'auth.login': { limit: 10, windowSeconds: Duration.minutes(15), scope: 'ip+identity' },
    'auth.register': { limit: 5, windowSeconds: Duration.hours(1), scope: 'ip' },
    /** Sending mail costs real money and real reputation, so it is the strictest. */
    'auth.emailDispatch': { limit: 5, windowSeconds: Duration.hours(1), scope: 'ip' },
    /** Redeeming is cheap but brute-forceable, so it is limited separately from sending. */
    'auth.tokenRedeem': { limit: 20, windowSeconds: Duration.hours(1), scope: 'ip' },
    'auth.passwordChange': { limit: 10, windowSeconds: Duration.hours(1), scope: 'identity' },
    'api.read': { limit: 300, windowSeconds: Duration.minutes(1), scope: 'ip' },
    'api.write': { limit: 60, windowSeconds: Duration.minutes(1), scope: 'ip+identity' },
  });

export interface RateLimitDecision {
  readonly allowed: boolean;
  readonly limit: number;
  readonly remaining: number;
  readonly resetAt: Date;
  readonly retryAfterSeconds: number;
}

/** Identifies the caller for keying purposes. */
export interface RateLimitSubject {
  readonly ip: string;
  /** User id when signed in, or the submitted identifier on sign-in attempts. */
  readonly identity: string | null;
}

/**
 * Builds the bucket key.
 *
 * The identity is hashed into the key by the caller when it is sensitive; this
 * function only decides which parts participate, so the same policy always
 * produces the same key shape.
 */
export function bucketKeyFor(
  policy: RateLimitPolicyName,
  scope: RateLimitScope,
  subject: RateLimitSubject,
): string {
  switch (scope) {
    case 'ip':
      return `${policy}|ip:${subject.ip}`;
    case 'identity':
      return `${policy}|id:${subject.identity ?? subject.ip}`;
    case 'ip+identity':
      return `${policy}|ip:${subject.ip}|id:${subject.identity ?? '-'}`;
  }
}

/**
 * The start of the fixed window a moment falls in.
 *
 * Fixed rather than sliding: a sliding window needs either a sorted set per key
 * or a row per request, and at this traffic level that cost buys nothing but a
 * slightly smoother edge.
 */
export function windowStartFor(now: Date, windowSeconds: number): Date {
  const ms = windowSeconds * 1000;

  return new Date(Math.floor(now.getTime() / ms) * ms);
}
