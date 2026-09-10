import { sql } from 'drizzle-orm';
import { index, jsonb, pgTable, smallint, text, varchar } from 'drizzle-orm/pg-core';

import { createdAt, nullableTimestamp, primaryId, requiredTimestamp } from './columns.js';

/**
 * Background jobs for the worker.
 *
 * Postgres-backed rather than Redis: the job rate here is low, and one fewer
 * stateful service is one fewer thing to operate and back up. `FOR UPDATE SKIP
 * LOCKED` gives safe concurrent reservation across workers without a
 * distributed lock.
 */
export const jobs = pgTable(
  'jobs',
  {
    id: primaryId(),
    queue: varchar('queue', { length: 64 }).notNull().default('default'),
    name: varchar('name', { length: 128 }).notNull(),
    payload: jsonb('payload').notNull().default({}),
    attempts: smallint('attempts').notNull().default(0),
    maxAttempts: smallint('max_attempts').notNull().default(5),
    availableAt: requiredTimestamp('available_at').defaultNow(),
    reservedAt: nullableTimestamp('reserved_at'),
    completedAt: nullableTimestamp('completed_at'),
    failedAt: nullableTimestamp('failed_at'),
    lastError: text('last_error'),
    createdAt: createdAt(),
  },
  (table) => [
    index('jobs_reservable_idx')
      .on(table.queue, table.availableAt)
      .where(
        sql`${table.reservedAt} IS NULL AND ${table.completedAt} IS NULL AND ${table.failedAt} IS NULL`,
      ),
    index('jobs_failed_idx')
      .on(table.failedAt.desc())
      .where(sql`${table.failedAt} IS NOT NULL`),
  ],
);
