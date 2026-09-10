import { defineId, type IdOf } from '@yume/core';

export const UserId = defineId<'UserId'>('user');
export type UserId = IdOf<typeof UserId>;

export const USER_STATUSES = ['pending_verification', 'active', 'suspended', 'deactivated'] as const;
export type UserStatus = (typeof USER_STATUSES)[number];

/**
 * The user aggregate.
 *
 * A readonly record with pure transition functions rather than a class with
 * twenty getters. Both express the same invariants, but this version has no
 * accessor boilerplate to keep in sync, serialises without a mapper, and makes
 * "did this function mutate my object?" a question the type system answers.
 *
 * Credentials deliberately live elsewhere: reading a profile must never pull a
 * password hash into memory.
 */
export interface User {
  readonly id: UserId;
  readonly username: string;
  readonly email: string;
  readonly status: UserStatus;
  readonly emailVerifiedAt: Date | null;
  readonly roles: readonly string[];
  readonly createdAt: Date;
  readonly updatedAt: Date;
}

export function canAuthenticate(user: User): boolean {
  return user.status === 'active' || user.status === 'pending_verification';
}

export function isEmailVerified(user: User): boolean {
  return user.emailVerifiedAt !== null;
}

export function hasRole(user: User, slug: string): boolean {
  return user.roles.includes(slug);
}
