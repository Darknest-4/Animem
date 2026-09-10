import { createHash } from 'node:crypto';

export const ROLLOUT_STRATEGIES = [
  'off',
  'on',
  'percentage',
  'role_list',
  'user_list',
  'ip_list',
] as const;
export type RolloutStrategy = (typeof ROLLOUT_STRATEGIES)[number];

export interface FeatureFlag {
  readonly key: string;
  readonly name: string;
  readonly description: string | null;
  readonly strategy: RolloutStrategy;
  readonly rolloutPercentage: number;
  readonly payload: FlagPayload;
  readonly expiresAt: Date | null;
  readonly updatedAt: Date;
}

export interface FlagPayload {
  readonly roles?: readonly string[];
  readonly users?: readonly string[];
  readonly ips?: readonly string[];
}

/** Who is asking. Everything a strategy can branch on, and nothing more. */
export interface FlagContext {
  readonly userId: string | null;
  readonly roles: readonly string[];
  readonly ip: string;
}

/**
 * Whether a flag is on for this actor.
 *
 * A pure function of the flag and the context, which is what makes it testable
 * without a database and identical everywhere it is called. The legacy site's
 * equivalent was a hardcoded array of route names and one hardcoded IP address
 * scattered across four files.
 */
export function isEnabledFor(flag: FeatureFlag, context: FlagContext, now: Date): boolean {
  // An expired flag is off regardless of strategy. Without this a flag nobody
  // cleaned up quietly becomes permanent configuration hidden in a table.
  if (flag.expiresAt !== null && flag.expiresAt.getTime() <= now.getTime()) {
    return false;
  }

  switch (flag.strategy) {
    case 'off':
      return false;
    case 'on':
      return true;
    case 'percentage':
      return inPercentage(flag, context);
    case 'role_list':
      return context.roles.some((role) => flag.payload.roles?.includes(role) ?? false);
    case 'user_list':
      return context.userId !== null && (flag.payload.users?.includes(context.userId) ?? false);
    case 'ip_list':
      return flag.payload.ips?.includes(context.ip) ?? false;
  }
}

/**
 * Stable bucketing.
 *
 * Hashing the flag key together with the identity means the same user gets the
 * same answer on every request — a flag that flickers per request is worse than
 * no flag, because half a page renders under each branch. Including the flag key
 * stops the same unlucky 5% being the guinea pigs for every rollout.
 */
function inPercentage(flag: FeatureFlag, context: FlagContext): boolean {
  if (flag.rolloutPercentage <= 0) {
    return false;
  }

  if (flag.rolloutPercentage >= 100) {
    return true;
  }

  const identity = context.userId ?? context.ip;
  const digest = createHash('sha256').update(`${flag.key}:${identity}`).digest();

  return (digest.readUInt32BE(0) % 100) < flag.rolloutPercentage;
}
