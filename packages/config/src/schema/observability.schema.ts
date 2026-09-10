import { z } from 'zod';

export const observabilitySchema = z.object({
  LOG_LEVEL: z.enum(['trace', 'debug', 'info', 'warn', 'error', 'fatal', 'silent']).default('info'),
  /** Human-readable output. Development only; production ships JSON lines. */
  LOG_PRETTY: z
    .string()
    .default('false')
    .transform((value) => ['1', 'true', 'yes', 'on'].includes(value.toLowerCase())),
  /** Environment override for a feature flag: FEATURE_<KEY>=true. */
  FEATURE_ENV_OVERRIDES: z
    .string()
    .default('true')
    .transform((value) => ['1', 'true', 'yes', 'on'].includes(value.toLowerCase())),
});

export type ObservabilityEnv = z.infer<typeof observabilitySchema>;
