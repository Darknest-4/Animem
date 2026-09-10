import { addSeconds, defineId, type IdOf } from '@yume/core';

import type { UserId } from '../../users/domain/user.types.js';

export const SessionId = defineId<'SessionId'>('session');
export type SessionId = IdOf<typeof SessionId>;

/**
 * A server-side authenticated session.
 *
 * The client holds an opaque token; this record holds its digest plus the
 * metadata needed to expire, rotate and revoke it. It is the replacement for a
 * `userID` cookie that was an unsigned, year-long, client-editable integer.
 */
export interface Session {
  readonly id: SessionId;
  readonly userId: UserId;
  readonly tokenHash: string;
  readonly createdIp: string;
  readonly createdUserAgent: string;
  readonly createdAt: Date;
  readonly lastSeenAt: Date;
  readonly expiresAt: Date;
  readonly revokedAt: Date | null;
  readonly revokedReason: string | null;
}

export interface SessionPolicy {
  readonly absoluteLifetimeSeconds: number;
  readonly idleExtensionSeconds: number;
  readonly rotateAfterSeconds: number;
  readonly maxConcurrent: number;
}

export function isRevoked(session: Session): boolean {
  return session.revokedAt !== null;
}

export function isExpired(session: Session, now: Date): boolean {
  return session.expiresAt.getTime() <= now.getTime();
}

export function isActive(session: Session, now: Date): boolean {
  return !isRevoked(session) && !isExpired(session, now);
}

export function shouldRotate(session: Session, policy: SessionPolicy, now: Date): boolean {
  return addSeconds(session.lastSeenAt, policy.rotateAfterSeconds).getTime() <= now.getTime();
}

/**
 * Whether the client fingerprint changed wholesale.
 *
 * Only the user agent is decisive. IP changes constantly on mobile networks, so
 * treating that as theft would sign people out for walking between cells.
 */
export function looksHijacked(session: Session, userAgent: string): boolean {
  if (session.createdUserAgent === '' || userAgent === '') {
    return false;
  }

  return session.createdUserAgent !== userAgent;
}
