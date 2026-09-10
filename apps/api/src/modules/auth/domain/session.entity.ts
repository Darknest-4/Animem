import { addSeconds } from '@yume/core';

import type { UserId } from '../../users/domain/user.types.js';
import { SessionId, type Session, type SessionPolicy } from './session.types.js';

export interface StartSessionInput {
  readonly userId: UserId;
  readonly tokenHash: string;
  readonly ip: string;
  readonly userAgent: string;
  readonly now: Date;
  readonly policy: SessionPolicy;
}

export function startSession(input: StartSessionInput): Session {
  return {
    id: SessionId.generate(),
    userId: input.userId,
    tokenHash: input.tokenHash,
    createdIp: input.ip,
    createdUserAgent: input.userAgent,
    createdAt: input.now,
    lastSeenAt: input.now,
    expiresAt: addSeconds(input.now, input.policy.absoluteLifetimeSeconds),
    revokedAt: null,
    revokedReason: null,
  };
}

/**
 * Records activity and slides the expiry.
 *
 * Never shortens it: the absolute lifetime set at sign-in is a ceiling, and a
 * short idle window must not pull it in.
 */
export function touchSession(session: Session, policy: SessionPolicy, now: Date): Session {
  const extended = addSeconds(now, policy.idleExtensionSeconds);

  return {
    ...session,
    lastSeenAt: now,
    expiresAt: extended.getTime() > session.expiresAt.getTime() ? extended : session.expiresAt,
  };
}

/**
 * Replaces the token digest while keeping the session row.
 *
 * Session-fixation defence that preserves the audit trail — a new row would lose
 * the original IP, user agent and creation time.
 */
export function rotateSessionToken(session: Session, tokenHash: string): Session {
  return { ...session, tokenHash };
}

export function revokeSession(session: Session, now: Date, reason: string): Session {
  if (session.revokedAt !== null) {
    // The first revocation is the audit record; a second must not overwrite it.
    return session;
  }

  return { ...session, revokedAt: now, revokedReason: reason };
}
