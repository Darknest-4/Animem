import { migrate } from 'drizzle-orm/postgres-js/migrator';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

import { createDatabase } from '../client.js';

/**
 * Applies pending migrations, then exits.
 *
 * Runs as its own container in the compose stack. `app` and `worker` are not
 * gated on it: a failed migration should surface as a failed job and a degraded
 * readiness probe, not as a stack that never starts.
 */
async function main(): Promise<void> {
  const url = process.env['DATABASE_URL'];

  if (url === undefined || url === '') {
    throw new Error('DATABASE_URL is required.');
  }

  const handle = createDatabase({
    url,
    // A migration runs alone; a pool would just hold idle connections.
    poolMax: 1,
    connectTimeoutSeconds: 10,
    idleTimeoutSeconds: 0,
    logQueries: false,
  });

  const migrationsFolder = resolve(dirname(fileURLToPath(import.meta.url)), '../../migrations');

  try {
    // Extensions the schema depends on. Created here rather than in a migration
    // because CREATE EXTENSION needs privileges a migration user may not keep,
    // and because both are idempotent.
    await handle.sql`CREATE EXTENSION IF NOT EXISTS pg_trgm`;
    await handle.sql`CREATE EXTENSION IF NOT EXISTS btree_gist`;

    await migrate(handle.db, { migrationsFolder });
    process.stdout.write('Migrations applied.\n');
  } finally {
    await handle.close();
  }
}

main().catch((error: unknown) => {
  process.stderr.write(`Migration failed: ${error instanceof Error ? error.message : String(error)}\n`);
  process.exitCode = 1;
});
