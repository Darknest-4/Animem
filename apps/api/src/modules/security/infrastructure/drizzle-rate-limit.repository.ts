import { and, eq, lt, sql } from 'drizzle-orm';

import { schema, type Executor } from '@yume/db';

import type { RateLimitRepository } from '../domain/rate-limit.repository.js';

/**
 * Postgres-backed counters.
 *
 * Deliberately not Redis. A second stateful service has to be deployed, secured,
 * backed up and reasoned about during a failover, and this workload is a handful
 * of upserts per second against a primary-key index. The repository interface is
 * the seam a Redis implementation drops into if measurement — rather than
 * fashion — ever calls for it.
 */
export class DrizzleRateLimitRepository implements RateLimitRepository {
  constructor(private readonly db: Executor) {}

  withExecutor(executor: Executor): DrizzleRateLimitRepository {
    return new DrizzleRateLimitRepository(executor);
  }

  async consume(bucketKey: string, windowStart: Date, expiresAt: Date): Promise<number> {
    // One statement, so the read-modify-write cannot interleave. `excluded` is
    // the row we tried to insert; the increment happens inside the same row lock
    // Postgres already took to resolve the conflict.
    const rows = await this.db
      .insert(schema.rateLimitBuckets)
      .values({ bucketKey, windowStart, hits: 1, expiresAt })
      .onConflictDoUpdate({
        target: [schema.rateLimitBuckets.bucketKey, schema.rateLimitBuckets.windowStart],
        set: { hits: sql`${schema.rateLimitBuckets.hits} + 1` },
      })
      .returning({ hits: schema.rateLimitBuckets.hits });

    return rows[0]?.hits ?? 1;
  }

  async peek(bucketKey: string, windowStart: Date): Promise<number> {
    const rows = await this.db
      .select({ hits: schema.rateLimitBuckets.hits })
      .from(schema.rateLimitBuckets)
      .where(
        and(
          eq(schema.rateLimitBuckets.bucketKey, bucketKey),
          eq(schema.rateLimitBuckets.windowStart, windowStart),
        ),
      )
      .limit(1);

    return rows[0]?.hits ?? 0;
  }

  async purgeExpired(now: Date): Promise<number> {
    const deleted = await this.db
      .delete(schema.rateLimitBuckets)
      .where(lt(schema.rateLimitBuckets.expiresAt, now))
      .returning({ bucketKey: schema.rateLimitBuckets.bucketKey });

    return deleted.length;
  }
}
