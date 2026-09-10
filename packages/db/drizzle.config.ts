import { defineConfig } from 'drizzle-kit';

/**
 * Migrations are generated from the TypeScript schema, reviewed, then committed.
 *
 * `push` is deliberately not used anywhere: a schema change that never becomes a
 * reviewable file is a schema change nobody can reproduce or roll back.
 */
export default defineConfig({
  // The compiled output, not the TypeScript source: drizzle-kit bundles the
  // schema as CommonJS and cannot resolve the explicit `.js` specifiers that
  // NodeNext ESM requires. `pnpm generate` builds first, so this is always fresh.
  schema: './dist/schema/index.js',
  out: './migrations',
  dialect: 'postgresql',
  dbCredentials: {
    url: process.env['DATABASE_URL'] ?? 'postgres://yume:yume@localhost:5432/yume',
  },
  verbose: true,
  strict: true,
});
