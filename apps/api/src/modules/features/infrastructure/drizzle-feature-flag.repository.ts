import { eq } from 'drizzle-orm';

import { NotFoundError, uuidV7 } from '@yume/core';
import { schema, type Database, type Executor } from '@yume/db';

import type { UserId } from '../../users/domain/user.types.js';
import type {
  FeatureFlagRepository,
  FeatureFlagUpdate,
} from '../domain/feature-flag.repository.js';
import type { FeatureFlag, FlagPayload } from '../domain/feature-flag.types.js';

type Row = typeof schema.featureFlags.$inferSelect;

function toFlag(row: Row): FeatureFlag {
  return {
    key: row.flagKey,
    name: row.name,
    description: row.description,
    strategy: row.strategy,
    rolloutPercentage: row.rolloutPercentage,
    payload: row.payload as FlagPayload,
    expiresAt: row.expiresAt,
    updatedAt: row.updatedAt,
  };
}

export class DrizzleFeatureFlagRepository implements FeatureFlagRepository {
  constructor(private readonly db: Database) {}

  async all(): Promise<readonly FeatureFlag[]> {
    const rows = await this.db.select().from(schema.featureFlags).orderBy(schema.featureFlags.flagKey);

    return rows.map(toFlag);
  }

  async findByKey(key: string): Promise<FeatureFlag | null> {
    const rows = await this.db
      .select()
      .from(schema.featureFlags)
      .where(eq(schema.featureFlags.flagKey, key))
      .limit(1);

    return rows[0] === undefined ? null : toFlag(rows[0]);
  }

  /**
   * Reads, writes and audits in one transaction.
   *
   * The before-state has to be read inside the transaction it is compared
   * against, or a concurrent change makes the audit trail describe a transition
   * that never happened.
   */
  async update(key: string, changes: FeatureFlagUpdate, changedBy: UserId | null): Promise<FeatureFlag> {
    return this.db.transaction(async (tx: Executor) => {
      const existing = await tx
        .select()
        .from(schema.featureFlags)
        .where(eq(schema.featureFlags.flagKey, key))
        .limit(1);

      const before = existing[0];

      if (before === undefined) {
        throw new NotFoundError('feature flag', key);
      }

      const updated = await tx
        .update(schema.featureFlags)
        .set({
          ...(changes.strategy === undefined ? {} : { strategy: changes.strategy }),
          ...(changes.rolloutPercentage === undefined
            ? {}
            : { rolloutPercentage: changes.rolloutPercentage }),
          ...(changes.payload === undefined ? {} : { payload: changes.payload }),
          ...(changes.expiresAt === undefined ? {} : { expiresAt: changes.expiresAt }),
          updatedAt: new Date(),
        })
        .where(eq(schema.featureFlags.flagKey, key))
        .returning();

      const after = updated[0];

      if (after === undefined) {
        throw new NotFoundError('feature flag', key);
      }

      await tx.insert(schema.featureFlagAudit).values({
        id: uuidV7(),
        flagKey: key,
        changedBy,
        beforeState: {
          strategy: before.strategy,
          rolloutPercentage: before.rolloutPercentage,
          payload: before.payload,
          expiresAt: before.expiresAt?.toISOString() ?? null,
        },
        afterState: {
          strategy: after.strategy,
          rolloutPercentage: after.rolloutPercentage,
          payload: after.payload,
          expiresAt: after.expiresAt?.toISOString() ?? null,
        },
      });

      return toFlag(after);
    });
  }
}
