import { sql } from 'drizzle-orm';
import {
  index,
  jsonb,
  pgTable,
  serial,
  smallint,
  text,
  uniqueIndex,
  uuid,
  varchar,
} from 'drizzle-orm/pg-core';

import { createdAt, nullableTimestamp, primaryId, updatedAt } from './columns.js';
import { rolloutStrategyEnum } from './enums.js';
import { users } from './users.schema.js';

/**
 * Runtime feature flags.
 *
 * This is the mechanism the legacy codebase lacked. It contained four unfinished
 * rewrites, gated by commented-out blocks, a hardcoded array of route names and
 * a hardcoded IP address — all of them permanently half-live because there was
 * no way to ship one dark.
 */
export const featureFlags = pgTable(
  'feature_flags',
  {
    id: serial('id').primaryKey(),
    flagKey: varchar('flag_key', { length: 100 }).notNull(),
    name: varchar('name', { length: 255 }).notNull(),
    description: text('description'),
    strategy: rolloutStrategyEnum('strategy').notNull().default('off'),
    rolloutPercentage: smallint('rollout_percentage').notNull().default(0),
    /** Strategy-specific: {roles: []}, {users: []}, {ips: []}. */
    payload: jsonb('payload').notNull().default({}),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
    /**
     * Every flag gets a deadline.
     *
     * A flag with no expiry quietly becomes permanent configuration, and
     * permanent configuration hidden in a flag table is how a codebase ends up
     * with four half-live rewrites.
     */
    expiresAt: nullableTimestamp('expires_at'),
  },
  (table) => [
    uniqueIndex('feature_flags_key_key').on(table.flagKey),
    index('feature_flags_expiring_idx')
      .on(table.expiresAt)
      .where(sql`${table.expiresAt} IS NOT NULL`),
  ],
);

/** Before and after state for every change, so a rollout is attributable. */
export const featureFlagAudit = pgTable(
  'feature_flag_audit',
  {
    id: primaryId(),
    flagKey: varchar('flag_key', { length: 100 }).notNull(),
    changedBy: uuid('changed_by').references(() => users.id, { onDelete: 'set null' }),
    beforeState: jsonb('before_state').notNull().default({}),
    afterState: jsonb('after_state').notNull().default({}),
    changedAt: createdAt(),
  },
  (table) => [index('feature_flag_audit_key_idx').on(table.flagKey, table.changedAt.desc())],
);
