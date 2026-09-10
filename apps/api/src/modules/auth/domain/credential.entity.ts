import { addSeconds } from '@yume/core';

import type { UserId } from '../../users/domain/user.types.js';
import type { Credential } from './credential.types.js';

export function createCredential(userId: UserId, passwordHash: string, now: Date): Credential {
  return {
    userId,
    passwordHash,
    algorithm: 'argon2id',
    passwordChangedAt: now,
    failedAttempts: 0,
    lockedUntil: null,
  };
}

/** A user-initiated change: clears the failure counter and any lock. */
export function replacePassword(credential: Credential, passwordHash: string, now: Date): Credential {
  return {
    ...credential,
    passwordHash,
    algorithm: 'argon2id',
    passwordChangedAt: now,
    failedAttempts: 0,
    lockedUntil: null,
  };
}

/**
 * A silent upgrade from a weaker hash.
 *
 * Not a password change: `passwordChangedAt` is untouched, because the user did
 * not choose a new password and "when did you last change it" must stay honest.
 */
export function upgradeHash(credential: Credential, passwordHash: string): Credential {
  return { ...credential, passwordHash, algorithm: 'argon2id' };
}

export function recordFailure(
  credential: Credential,
  now: Date,
  maxAttempts: number,
  lockSeconds: number,
): Credential {
  const failedAttempts = credential.failedAttempts + 1;

  if (failedAttempts < maxAttempts) {
    return { ...credential, failedAttempts };
  }

  // The counter resets with the lock, so the next window starts clean rather
  // than locking again on the first mistake after it expires.
  return { ...credential, failedAttempts: 0, lockedUntil: addSeconds(now, lockSeconds) };
}

export function recordSuccess(credential: Credential): Credential {
  return { ...credential, failedAttempts: 0, lockedUntil: null };
}
