import { z } from 'zod';

export const databaseSchema = z.object({
  DATABASE_URL: z
    .string()
    .url()
    .refine((value) => value.startsWith('postgres://') || value.startsWith('postgresql://'), {
      message: 'DATABASE_URL must be a postgres:// connection string.',
    }),
  DATABASE_POOL_MAX: z.coerce.number().int().min(1).max(100).default(10),
  DATABASE_CONNECT_TIMEOUT_SECONDS: z.coerce.number().int().min(1).max(60).default(5),
  DATABASE_IDLE_TIMEOUT_SECONDS: z.coerce.number().int().min(0).max(3600).default(30),
  /** Logs every statement. Development only — it is loud and it prints values. */
  DATABASE_LOG_QUERIES: z
    .string()
    .default('false')
    .transform((value) => ['1', 'true', 'yes', 'on'].includes(value.toLowerCase())),
});

export type DatabaseEnv = z.infer<typeof databaseSchema>;
