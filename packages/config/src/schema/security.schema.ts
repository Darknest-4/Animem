import { z } from 'zod';

const booleanish = (fallback: 'true' | 'false') =>
  z
    .string()
    .default(fallback)
    .transform((value) => ['1', 'true', 'yes', 'on'].includes(value.toLowerCase()));

const csv = (fallback: string) =>
  z
    .string()
    .default(fallback)
    .transform((value) =>
      value
        .split(',')
        .map((entry) => entry.trim())
        .filter((entry) => entry.length > 0),
    );

export const securitySchema = z.object({
  /**
   * Signs session and CSRF material. 32 bytes minimum, and required with no
   * default — a fallback secret is the same as no secret.
   */
  APP_SECRET: z.string().min(32, 'APP_SECRET must be at least 32 characters.'),

  SESSION_ABSOLUTE_LIFETIME_SECONDS: z.coerce.number().int().min(60).default(2_592_000),
  SESSION_IDLE_EXTENSION_SECONDS: z.coerce.number().int().min(60).default(1_209_600),
  SESSION_ROTATE_AFTER_SECONDS: z.coerce.number().int().min(60).default(3_600),
  SESSION_MAX_CONCURRENT: z.coerce.number().int().min(1).max(100).default(10),
  SESSION_COOKIE_NAME: z.string().default('yume_session'),
  /** Off only for plain-HTTP local development. */
  SESSION_COOKIE_SECURE: booleanish('true'),

  PASSWORD_MIN_LENGTH: z.coerce.number().int().min(8).max(128).default(12),
  ARGON2_MEMORY_COST_KIB: z.coerce.number().int().min(8192).default(65_536),
  ARGON2_TIME_COST: z.coerce.number().int().min(2).default(4),
  ARGON2_PARALLELISM: z.coerce.number().int().min(1).default(2),
  AUTH_MAX_FAILED_ATTEMPTS: z.coerce.number().int().min(3).default(8),
  AUTH_LOCK_SECONDS: z.coerce.number().int().min(60).default(900),

  /** Only these may set X-Forwarded-For; anything else is a risk signal. */
  TRUSTED_PROXIES: csv('127.0.0.1,10.0.0.0/8,172.16.0.0/12'),
  /** Exact origins. Never a wildcard: credentials are allowed. */
  CORS_ALLOWED_ORIGINS: csv('http://localhost:3000'),
  SECURITY_HSTS: booleanish('true'),

  RISK_THRESHOLD_MONITOR: z.coerce.number().int().min(0).max(100).default(20),
  RISK_THRESHOLD_RATE_LIMIT: z.coerce.number().int().min(0).max(100).default(40),
  RISK_THRESHOLD_CHALLENGE: z.coerce.number().int().min(0).max(100).default(60),
  RISK_THRESHOLD_RESTRICT: z.coerce.number().int().min(0).max(100).default(75),
  RISK_THRESHOLD_BLOCK: z.coerce.number().int().min(0).max(100).default(90),
  RISK_ALLOWED_USER_AGENTS: csv(''),
});

export type SecurityEnv = z.infer<typeof securitySchema>;
