import { canonicaliseEmail, canonicaliseUsername } from '../../../shared/index.js';
import { UserId, type User } from './user.types.js';

export interface NewUserInput {
  readonly username: string;
  readonly email: string;
  readonly now: Date;
  readonly roles?: readonly string[];
  readonly requiresEmailVerification?: boolean;
}

/**
 * Mints a user.
 *
 * The canonical forms are derived here rather than at the persistence boundary,
 * so every path that creates a user — HTTP registration, the CLI, an import —
 * gets the same duplicate-detection keys.
 */
export function createUser(input: NewUserInput): User & {
  readonly usernameCanonical: string;
  readonly emailCanonical: string;
} {
  const requiresVerification = input.requiresEmailVerification ?? true;
  const username = input.username.trim();
  const email = input.email.trim().toLowerCase();

  return {
    id: UserId.generate(),
    username,
    email,
    usernameCanonical: canonicaliseUsername(username),
    emailCanonical: canonicaliseEmail(email),
    status: requiresVerification ? 'pending_verification' : 'active',
    emailVerifiedAt: requiresVerification ? null : input.now,
    roles: input.roles ?? ['user'],
    createdAt: input.now,
    updatedAt: input.now,
  };
}

/** Confirms the address and, if that was the only thing pending, activates. */
export function markEmailVerified(user: User, now: Date): User {
  if (user.emailVerifiedAt !== null) {
    return user;
  }

  return {
    ...user,
    emailVerifiedAt: now,
    status: user.status === 'pending_verification' ? 'active' : user.status,
    updatedAt: now,
  };
}

export function suspend(user: User, now: Date): User {
  return { ...user, status: 'suspended', updatedAt: now };
}

export function reinstate(user: User, now: Date): User {
  return {
    ...user,
    status: user.emailVerifiedAt === null ? 'pending_verification' : 'active',
    updatedAt: now,
  };
}

/**
 * A changed address is unverified again.
 *
 * Otherwise changing it to an address you do not control would keep the
 * "verified" badge and, worse, redirect password resets there.
 */
export function changeEmail(user: User, email: string, now: Date): User & { readonly emailCanonical: string } {
  const normalised = email.trim().toLowerCase();

  return {
    ...user,
    email: normalised,
    emailCanonical: canonicaliseEmail(normalised),
    emailVerifiedAt: null,
    status: 'pending_verification',
    updatedAt: now,
  };
}
