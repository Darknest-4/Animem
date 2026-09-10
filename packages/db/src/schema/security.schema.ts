import { relations, sql } from 'drizzle-orm';
import {
  cidr,
  index,
  inet,
  integer,
  jsonb,
  pgTable,
  primaryKey,
  smallint,
  text,
  uuid,
  varchar,
} from 'drizzle-orm/pg-core';

import { createdAt, nullableTimestamp, primaryId, requiredTimestamp } from './columns.js';
import { banScopeEnum, banTypeEnum, networkClassificationEnum, severityEnum } from './enums.js';
import { users } from './users.schema.js';

/**
 * Append-only audit trail.
 *
 * The legacy site had none, which meant a compromise there would have been both
 * undetectable and unreconstructable.
 */
export const securityEvents = pgTable(
  'security_events',
  {
    id: primaryId(),
    eventType: varchar('event_type', { length: 64 }).notNull(),
    severity: severityEnum('severity').notNull().default('info'),
    userId: uuid('user_id').references(() => users.id, { onDelete: 'set null' }),
    ip: inet('ip').notNull(),
    userAgent: varchar('user_agent', { length: 512 }).notNull().default(''),
    method: varchar('method', { length: 10 }).notNull().default(''),
    path: varchar('path', { length: 512 }).notNull().default(''),
    riskScore: smallint('risk_score').notNull().default(0),
    metadata: jsonb('metadata').notNull().default({}),
    occurredAt: createdAt(),
  },
  (table) => [
    // Serves the risk engine's "recent failed logins from this address" counter.
    index('security_events_ip_type_time_idx').on(
      table.ip,
      table.eventType,
      table.occurredAt.desc(),
    ),
    index('security_events_user_time_idx').on(table.userId, table.occurredAt.desc()),
    index('security_events_severity_idx')
      .on(table.severity, table.occurredAt.desc())
      .where(sql`${table.severity} IN ('warning', 'critical')`),
  ],
);

export const bans = pgTable(
  'bans',
  {
    id: primaryId(),
    scope: banScopeEnum('scope').notNull(),
    banType: banTypeEnum('ban_type').notNull(),
    /** IP, subnet key, or user id depending on scope. Null for a global ban. */
    subject: varchar('subject', { length: 128 }),
    reason: text('reason').notNull(),
    createdAt: createdAt(),
    /** Null means permanent. */
    expiresAt: nullableTimestamp('expires_at'),
    liftedAt: nullableTimestamp('lifted_at'),
    createdBy: uuid('created_by').references(() => users.id, { onDelete: 'set null' }),
  },
  (table) => [
    index('bans_active_idx')
      .on(table.scope, table.subject)
      .where(sql`${table.liftedAt} IS NULL`),
  ],
);

/**
 * Fixed-window rate-limit counters.
 *
 * Postgres-backed rather than Redis: at this write rate an atomic upsert is
 * correct and it keeps the production stack at one stateful service. The
 * repository interface is the seam a Redis implementation drops into when
 * measurements, rather than fashion, say it should.
 */
export const rateLimitBuckets = pgTable(
  'rate_limit_buckets',
  {
    bucketKey: varchar('bucket_key', { length: 255 }).notNull(),
    windowStart: requiredTimestamp('window_start'),
    hits: integer('hits').notNull().default(0),
    expiresAt: requiredTimestamp('expires_at'),
  },
  (table) => [
    primaryKey({ columns: [table.bucketKey, table.windowStart] }),
    index('rate_limit_buckets_expiry_idx').on(table.expiresAt),
  ],
);

/**
 * Locally refreshed network classifications.
 *
 * A detector must never call a third-party reputation API on the request path;
 * this table is what it reads instead.
 */
export const networkReputation = pgTable(
  'network_reputation',
  {
    network: cidr('network').primaryKey(),
    classification: networkClassificationEnum('classification').notNull(),
    asn: integer('asn'),
    organisation: varchar('organisation', { length: 255 }),
    refreshedAt: createdAt(),
  },
  (table) => [
    // GiST so `network >>= :ip` (contains) is an index lookup, not a scan.
    index('network_reputation_contains_idx').using('gist', table.network),
  ],
);

export const securityEventsRelations = relations(securityEvents, ({ one }) => ({
  user: one(users, { fields: [securityEvents.userId], references: [users.id] }),
}));

export const bansRelations = relations(bans, ({ one }) => ({
  createdByUser: one(users, { fields: [bans.createdBy], references: [users.id] }),
}));
