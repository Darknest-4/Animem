import type { UserId } from '../../users/domain/user.types.js';

export type PasswordAlgorithm = 'argon2id' | 'legacy_sha256';

/**
 * A user's password credential.
 *
 * `legacy_sha256` is read-only: it exists so the ten-year-old unsalted hashes
 * can be upgraded during a normal sign-in rather than forcing a reset on every
 * account. Nothing writes it.
 */
export interface Credential {
  readonly userId: UserId;
  readonly passwordHash: string;
  readonly algorithm: PasswordAlgorithm;
  readonly passwordChangedAt: Date | null;
  readonly failedAttempts: number;
  readonly lockedUntil: Date | null;
}

export function isLocked(credential: Credential, now: Date): boolean {
  return credential.lockedUntil !== null && credential.lockedUntil.getTime() > now.getTime();
}

export function isLegacy(credential: Credential): boolean {
  return credential.algorithm === 'legacy_sha256';
}
