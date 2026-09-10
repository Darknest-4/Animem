import { z } from 'zod';

export const appSchema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']).default('development'),
  APP_NAME: z.string().default('Yume'),
  APP_VERSION: z.string().default('dev'),
  /** Public origin of the API itself, used to build links in outgoing mail. */
  APP_URL: z.string().url().default('http://localhost:4000'),
  /** Public origin of the web front end, where those links actually land. */
  WEB_URL: z.string().url().default('http://localhost:3000'),
  API_HOST: z.string().default('0.0.0.0'),
  API_PORT: z.coerce.number().int().min(1).max(65535).default(4000),
  /**
   * Adds internal detail to error responses. Never defaults to true: a missing
   * value must not be the thing that starts leaking stack traces.
   */
  APP_DEBUG: z
    .string()
    .default('false')
    .transform((value) => ['1', 'true', 'yes', 'on'].includes(value.toLowerCase())),
});

export type AppEnv = z.infer<typeof appSchema>;
