import { sql } from 'drizzle-orm';
import { timestamp, uuid } from 'drizzle-orm/pg-core';

/**
 * Column fragments repeated across tables.
 *
 * Spelled once so `created_at` cannot end up as `timestamptz` in one table and
 * `timestamp` in another — which is exactly how the legacy schema ended up
 * comparing unix integers against quoted strings.
 */

/** A UUIDv7 primary key. The application mints it, so inserts stay explicit. */
export const primaryId = () => uuid('id').primaryKey();

export const createdAt = () =>
  timestamp('created_at', { withTimezone: true, mode: 'date' })
    .notNull()
    .defaultNow();

export const updatedAt = () =>
  timestamp('updated_at', { withTimezone: true, mode: 'date' })
    .notNull()
    .defaultNow();

/** A nullable point in time, e.g. revoked_at, consumed_at, expires_at. */
export const nullableTimestamp = (name: string) =>
  timestamp(name, { withTimezone: true, mode: 'date' });

export const requiredTimestamp = (name: string) =>
  timestamp(name, { withTimezone: true, mode: 'date' }).notNull();

/** now() as a SQL default, for use inside migrations and defaults. */
export const nowSql = sql`now()`;
