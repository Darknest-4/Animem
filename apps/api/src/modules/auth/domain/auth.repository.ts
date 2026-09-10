import type { UserId } from '../../users/domain/user.types.js';
import type { Credential } from './credential.types.js';
import type { OneTimeToken, TokenPurpose } from './one-time-token.types.js';
import type { Session, SessionId } from './session.types.js';

export interface SessionRepository {
  findByTokenHash(tokenHash: string): Promise<Session | null>;
  findById(id: SessionId): Promise<Session | null>;
  /** Active sessions, newest activity first. */
  findActiveForUser(userId: UserId, now: Date): Promise<readonly Session[]>;
  insert(session: Session): Promise<void>;
  update(session: Session): Promise<void>;
  revokeAllForUser(userId: UserId, now: Date, reason: string): Promise<number>;
  /** Housekeeping: drops rows that expired long ago. */
  deleteExpiredBefore(cutoff: Date): Promise<number>;
}

export interface CredentialRepository {
  findForUser(userId: UserId): Promise<Credential | null>;
  insert(credential: Credential): Promise<void>;
  update(credential: Credential): Promise<void>;
}

export interface OneTimeTokenRepository {
  findByHash(purpose: TokenPurpose, tokenHash: string): Promise<OneTimeToken | null>;
  insert(token: OneTimeToken): Promise<void>;
  markConsumed(id: OneTimeToken['id'], now: Date): Promise<void>;
  /**
   * Invalidates every outstanding token of this purpose for the user.
   *
   * Called before issuing a new one, so requesting a second link makes the first
   * dead rather than leaving two valid links in two places.
   */
  consumeAllForUser(userId: UserId, purpose: TokenPurpose, now: Date): Promise<number>;
  deleteExpiredBefore(cutoff: Date): Promise<number>;
}
