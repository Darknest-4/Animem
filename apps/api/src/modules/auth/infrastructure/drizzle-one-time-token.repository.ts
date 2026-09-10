import { and, eq, isNull, lt } from 'drizzle-orm';

import { schema, type Executor } from '@yume/db';

import { UserId, type User } from '../../users/domain/user.types.js';
import type { OneTimeTokenRepository } from '../domain/auth.repository.js';
import {
  OneTimeTokenId,
  type OneTimeToken,
  type TokenPurpose,
} from '../domain/one-time-token.types.js';

/**
 * One table for both purposes.
 *
 * The PHP version had two tables with identical columns and a repository full of
 * match statements to pick between them; the purpose column expresses the same
 * thing without the branching, and a third kind of link is an enum member rather
 * than a fourth copy of this file.
 */
export class DrizzleOneTimeTokenRepository implements OneTimeTokenRepository {
  constructor(private readonly db: Executor) {}

  withExecutor(executor: Executor): DrizzleOneTimeTokenRepository {
    return new DrizzleOneTimeTokenRepository(executor);
  }

  async findByHash(purpose: TokenPurpose, tokenHash: string): Promise<OneTimeToken | null> {
    const rows = await this.db
      .select()
      .from(schema.oneTimeTokens)
      .where(
        and(eq(schema.oneTimeTokens.purpose, purpose), eq(schema.oneTimeTokens.tokenHash, tokenHash)),
      )
      .limit(1);

    const row = rows[0];

    if (row === undefined) {
      return null;
    }

    return {
      id: OneTimeTokenId.of(row.id),
      userId: UserId.of(row.userId),
      purpose: row.purpose,
      tokenHash: row.tokenHash,
      requestedIp: row.requestedIp,
      createdAt: row.createdAt,
      expiresAt: row.expiresAt,
      consumedAt: row.consumedAt,
    };
  }

  async insert(token: OneTimeToken): Promise<void> {
    await this.db.insert(schema.oneTimeTokens).values({
      id: token.id,
      userId: token.userId,
      purpose: token.purpose,
      tokenHash: token.tokenHash,
      requestedIp: token.requestedIp,
      createdAt: token.createdAt,
      expiresAt: token.expiresAt,
      consumedAt: token.consumedAt,
    });
  }

  async markConsumed(id: OneTimeToken['id'], now: Date): Promise<void> {
    await this.db
      .update(schema.oneTimeTokens)
      .set({ consumedAt: now })
      .where(eq(schema.oneTimeTokens.id, id));
  }

  async consumeAllForUser(userId: User['id'], purpose: TokenPurpose, now: Date): Promise<number> {
    const consumed = await this.db
      .update(schema.oneTimeTokens)
      .set({ consumedAt: now })
      .where(
        and(
          eq(schema.oneTimeTokens.userId, userId),
          eq(schema.oneTimeTokens.purpose, purpose),
          isNull(schema.oneTimeTokens.consumedAt),
        ),
      )
      .returning({ id: schema.oneTimeTokens.id });

    return consumed.length;
  }

  async deleteExpiredBefore(cutoff: Date): Promise<number> {
    const deleted = await this.db
      .delete(schema.oneTimeTokens)
      .where(lt(schema.oneTimeTokens.expiresAt, cutoff))
      .returning({ id: schema.oneTimeTokens.id });

    return deleted.length;
  }
}
