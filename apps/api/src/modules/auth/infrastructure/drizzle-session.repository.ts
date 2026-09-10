import { and, desc, eq, gt, isNull, lt, sql } from 'drizzle-orm';

import { schema, type Executor } from '@yume/db';

import { UserId, type User } from '../../users/domain/user.types.js';
import type { SessionRepository } from '../domain/auth.repository.js';
import { SessionId, type Session } from '../domain/session.types.js';

type Row = typeof schema.sessions.$inferSelect;

function toSession(row: Row): Session {
  return {
    id: SessionId.of(row.id),
    userId: UserId.of(row.userId),
    tokenHash: row.tokenHash,
    createdIp: row.createdIp,
    createdUserAgent: row.createdUserAgent,
    createdAt: row.createdAt,
    lastSeenAt: row.lastSeenAt,
    expiresAt: row.expiresAt,
    revokedAt: row.revokedAt,
    revokedReason: row.revokedReason,
  };
}

export class DrizzleSessionRepository implements SessionRepository {
  constructor(private readonly db: Executor) {}

  withExecutor(executor: Executor): DrizzleSessionRepository {
    return new DrizzleSessionRepository(executor);
  }

  async findByTokenHash(tokenHash: string): Promise<Session | null> {
    const rows = await this.db
      .select()
      .from(schema.sessions)
      .where(eq(schema.sessions.tokenHash, tokenHash))
      .limit(1);

    return rows[0] === undefined ? null : toSession(rows[0]);
  }

  async findById(id: Session['id']): Promise<Session | null> {
    const rows = await this.db.select().from(schema.sessions).where(eq(schema.sessions.id, id)).limit(1);

    return rows[0] === undefined ? null : toSession(rows[0]);
  }

  async findActiveForUser(userId: User['id'], now: Date): Promise<readonly Session[]> {
    const rows = await this.db
      .select()
      .from(schema.sessions)
      .where(
        and(
          eq(schema.sessions.userId, userId),
          isNull(schema.sessions.revokedAt),
          gt(schema.sessions.expiresAt, now),
        ),
      )
      .orderBy(desc(schema.sessions.lastSeenAt));

    return rows.map(toSession);
  }

  async insert(session: Session): Promise<void> {
    await this.db.insert(schema.sessions).values({
      id: session.id,
      userId: session.userId,
      tokenHash: session.tokenHash,
      createdIp: session.createdIp,
      createdUserAgent: session.createdUserAgent,
      createdAt: session.createdAt,
      lastSeenAt: session.lastSeenAt,
      expiresAt: session.expiresAt,
      revokedAt: session.revokedAt,
      revokedReason: session.revokedReason,
    });
  }

  async update(session: Session): Promise<void> {
    await this.db
      .update(schema.sessions)
      .set({
        tokenHash: session.tokenHash,
        lastSeenAt: session.lastSeenAt,
        expiresAt: session.expiresAt,
        revokedAt: session.revokedAt,
        revokedReason: session.revokedReason,
      })
      .where(eq(schema.sessions.id, session.id));
  }

  async revokeAllForUser(userId: User['id'], now: Date, reason: string): Promise<number> {
    const revoked = await this.db
      .update(schema.sessions)
      .set({ revokedAt: now, revokedReason: reason })
      .where(and(eq(schema.sessions.userId, userId), isNull(schema.sessions.revokedAt)))
      .returning({ id: schema.sessions.id });

    return revoked.length;
  }

  async deleteExpiredBefore(cutoff: Date): Promise<number> {
    const deleted = await this.db
      .delete(schema.sessions)
      .where(lt(schema.sessions.expiresAt, cutoff))
      .returning({ id: schema.sessions.id });

    return deleted.length;
  }

  /** Used by the stats endpoint; kept here so the table stays in one module. */
  async countActive(now: Date): Promise<number> {
    const rows = await this.db
      .select({ value: sql<number>`count(*)::int` })
      .from(schema.sessions)
      .where(and(isNull(schema.sessions.revokedAt), gt(schema.sessions.expiresAt, now)));

    return rows[0]?.value ?? 0;
  }
}
