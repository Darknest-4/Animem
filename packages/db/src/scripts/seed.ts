import { createDatabase } from '../client.js';
import { seed } from '../seed/seed.js';

async function main(): Promise<void> {
  const url = process.env['DATABASE_URL'];

  if (url === undefined || url === '') {
    throw new Error('DATABASE_URL is required.');
  }

  const handle = createDatabase({
    url,
    poolMax: 1,
    connectTimeoutSeconds: 10,
    idleTimeoutSeconds: 0,
    logQueries: false,
  });

  try {
    const result = await seed(handle.db);
    process.stdout.write(
      `Seeded ${String(result.permissions)} permissions, ${String(result.roles)} roles, ` +
        `${String(result.grants)} new grants, ${String(result.featureFlags)} feature flags.\n`,
    );
  } finally {
    await handle.close();
  }
}

main().catch((error: unknown) => {
  process.stderr.write(`Seed failed: ${error instanceof Error ? error.message : String(error)}\n`);
  process.exitCode = 1;
});
