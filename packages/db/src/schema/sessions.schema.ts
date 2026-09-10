import { relations, sql } from 'drizzle-orm';
import { char, index, inet, pgTable, uniqueIndex, uuid, varchar } from 'drizzle-orm/pg-core';

import { createdAt, nullableTimestamp, primaryId, requiredTimestamp } from './columns.js';
import { tokenPurposeEnum } from './enums.js';
import { users } from './users.schema.js';

/**
 * Server-side sessions.
 *
 * The client holds an opaque token; only its SHA-256 digest lives here, so a
 * dump of this table yields nothing presentable. This replaces the legacy
 * `userID` cookie, which was an unsigned, year-long, client-editable integer
 * that granted whatever account it named.
 */
export const sessions = pgTable(
  'sessions',
  {
    id: primaryId(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    tokenHash: char('token_hash', { length: 64 }).notNull(),
    createdIp: inet('created_ip').notNull(),
    createdUserAgent: varchar('created_user_agent', { length: 512 }).notNull().default(''),
    createdAt: createdAt(),
    lastSeenAt: requiredTimestamp('last_seen_at').defaultNow(),
    expiresAt: requiredTimestamp('expires_at'),
    revokedAt: nullableTimestamp('revoked_at'),
    revokedReason: varchar('revoked_reason', { length: 64 }),
  },
  (table) => [
    uniqueIndex('sessions_token_hash_key').on(table.tokenHash),
    // Both partial: a revoked session is never looked up again, only reported.
    index('sessions_user_active_idx')
      .on(table.userId, table.lastSeenAt.desc())
      .where(sql`${table.revokedAt} IS NULL`),
    index('sessions_expires_at_idx')
      .on(table.expiresAt)
      .where(sql`${table.revokedAt} IS NULL`),
  ],
);

/**
 * Email verification and password reset tokens in one table.
 *
 * The PHP version had two tables with identical columns and one repository full
 * of match statements to choose between them. A purpose column expresses the
 * same thing without the duplication, and adding a third kind of link later is
 * an enum member rather than a fourth copy of the same code.
 */
export const oneTimeTokens = pgTable(
  'one_time_tokens',
  {
    id: primaryId(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    purpose: tokenPurposeEnum('purpose').notNull(),
    tokenHash: char('token_hash', { length: 64 }).notNull(),
    requestedIp: inet('requested_ip'),
    createdAt: createdAt(),
    expiresAt: requiredTimestamp('expires_at'),
    /** Recorded rather than deleted, so a replayed link is distinguishable
     *  from an expired one in the audit trail. */
    consumedAt: nullableTimestamp('consumed_at'),
  },
  (table) => [
    uniqueIndex('one_time_tokens_hash_key').on(table.tokenHash),
    index('one_time_tokens_user_purpose_idx')
      .on(table.userId, table.purpose)
      .where(sql`${table.consumedAt} IS NULL`),
    index('one_time_tokens_expiry_idx').on(table.expiresAt),
  ],
);

export const sessionsRelations = relations(sessions, ({ one }) => ({
  user: one(users, { fields: [sessions.userId], references: [users.id] }),
}));

export const oneTimeTokensRelations = relations(oneTimeTokens, ({ one }) => ({
  user: one(users, { fields: [oneTimeTokens.userId], references: [users.id] }),
}));
