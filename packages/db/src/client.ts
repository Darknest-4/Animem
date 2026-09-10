import { drizzle, type PostgresJsDatabase } from 'drizzle-orm/postgres-js';
import postgres, { type Sql } from 'postgres';

import type { Logger } from '@yume/logger';

import * as schema from './schema/index.js';

export type Schema = typeof schema;
export type Database = PostgresJsDatabase<Schema>;

/**
 * A transaction handle.
 *
 * Repositories accept `Database | Transaction` so a use case can compose several
 * of them atomically without every repository growing a second set of methods.
 */
export type Transaction = Parameters<Parameters<Database['transaction']>[0]>[0];

/** Either a pooled connection or an open transaction. */
export type Executor = Database | Transaction;

export interface DatabaseOptions {
  readonly url: string;
  readonly poolMax: number;
  readonly connectTimeoutSeconds: number;
  readonly idleTimeoutSeconds: number;
  readonly logQueries: boolean;
  readonly logger?: Logger;
}

export interface DatabaseHandle {
  readonly db: Database;
  readonly sql: Sql;
  close(): Promise<void>;
  ping(): Promise<void>;
}

export function createDatabase(options: DatabaseOptions): DatabaseHandle {
  const client = postgres(options.url, {
    max: options.poolMax,
    connect_timeout: options.connectTimeoutSeconds,
    idle_timeout: options.idleTimeoutSeconds,
    // Recycle connections so a long-lived pool cannot accumulate server-side
    // state or hold a socket the network has quietly dropped.
    max_lifetime: 60 * 30,
    // NOTICE output is useful while developing and pure noise in production.
    // postgres.js returns NUMERIC as a string by default, which is what the
    // `score numeric(4,2)` column needs — parsing it as a float would turn 8.70
    // into 8.699999999999999.
    ...(options.logQueries ? {} : { onnotice: () => undefined }),
  });

  const db = drizzle(client, {
    schema,
    logger: options.logQueries
      ? {
          logQuery(query, params) {
            options.logger?.debug('sql', { query, params });
          },
        }
      : false,
  });

  return {
    db,
    sql: client,
    async close(): Promise<void> {
      await client.end({ timeout: 5 });
    },
    async ping(): Promise<void> {
      await client`SELECT 1`;
    },
  };
}
