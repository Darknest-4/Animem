import { relations, sql } from 'drizzle-orm';
import { index, pgTable, smallint, text, uniqueIndex, uuid, varchar } from 'drizzle-orm/pg-core';

import { createdAt, nullableTimestamp, primaryId, updatedAt } from './columns.js';
import { passwordAlgorithmEnum, userStatusEnum } from './enums.js';

export const users = pgTable(
  'users',
  {
    id: primaryId(),
    username: varchar('username', { length: 32 }).notNull(),
    /**
     * Lower-cased with separators removed, so `kitsune`, `Kit.Sune`, `kit-sune`
     * and `kit_sune` collide. Impersonation by near-identical spelling is the
     * threat this closes; the unique index below enforces it, rather than a
     * SELECT-then-INSERT check that races under concurrent registration.
     */
    usernameCanonical: varchar('username_canonical', { length: 32 }).notNull(),
    email: varchar('email', { length: 254 }).notNull(),
    /** Gmail dots and +tags folded, so one inbox cannot hold two accounts. */
    emailCanonical: varchar('email_canonical', { length: 254 }).notNull(),
    status: userStatusEnum('status').notNull().default('pending_verification'),
    emailVerifiedAt: nullableTimestamp('email_verified_at'),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (table) => [
    uniqueIndex('users_username_canonical_key').on(table.usernameCanonical),
    uniqueIndex('users_email_canonical_key').on(table.emailCanonical),
    index('users_created_at_idx').on(table.createdAt.desc()),
    // Partial: nearly every row is 'active', so a full index on status would be
    // a table scan wearing an index's clothes. Only the exceptions are worth it.
    index('users_status_idx')
      .on(table.status)
      .where(sql`${table.status} <> 'active'`),
  ],
);

/**
 * Password material, split from the user row.
 *
 * A profile read must not pull a hash into memory, and the two have completely
 * different access patterns: one is read on every request, the other only at
 * sign-in.
 */
export const userCredentials = pgTable('user_credentials', {
  userId: uuid('user_id')
    .primaryKey()
    .references(() => users.id, { onDelete: 'cascade' }),
  passwordHash: text('password_hash').notNull(),
  passwordAlgorithm: passwordAlgorithmEnum('password_algorithm').notNull().default('argon2id'),
  passwordChangedAt: nullableTimestamp('password_changed_at'),
  failedAttempts: smallint('failed_attempts').notNull().default(0),
  lockedUntil: nullableTimestamp('locked_until'),
  updatedAt: updatedAt(),
});

export const usersRelations = relations(users, ({ one }) => ({
  credentials: one(userCredentials, {
    fields: [users.id],
    references: [userCredentials.userId],
  }),
}));

export const userCredentialsRelations = relations(userCredentials, ({ one }) => ({
  user: one(users, { fields: [userCredentials.userId], references: [users.id] }),
}));
