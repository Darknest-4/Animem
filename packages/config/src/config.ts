import { z } from 'zod';

import {
  appSchema,
  databaseSchema,
  mailSchema,
  observabilitySchema,
  securitySchema,
} from './schema/index.js';

const envSchema = appSchema
  .merge(databaseSchema)
  .merge(securitySchema)
  .merge(mailSchema)
  .merge(observabilitySchema);

export type Env = z.infer<typeof envSchema>;

/**
 * The shape the rest of the system reads.
 *
 * Grouped by concern rather than exposed as a flat bag of env names, so a
 * consumer asks for `config.session.absoluteLifetimeSeconds` and never has to
 * know that it came from SESSION_ABSOLUTE_LIFETIME_SECONDS.
 */
export interface Config {
  readonly env: Env['NODE_ENV'];
  readonly isProduction: boolean;
  readonly isTest: boolean;
  readonly debug: boolean;

  readonly app: {
    readonly name: string;
    readonly version: string;
    readonly url: string;
    readonly webUrl: string;
    readonly host: string;
    readonly port: number;
  };

  readonly database: {
    readonly url: string;
    readonly poolMax: number;
    readonly connectTimeoutSeconds: number;
    readonly idleTimeoutSeconds: number;
    readonly logQueries: boolean;
  };

  readonly security: {
    readonly appSecret: string;
    readonly trustedProxies: readonly string[];
    readonly corsAllowedOrigins: readonly string[];
    readonly hsts: boolean;
  };

  readonly session: {
    readonly absoluteLifetimeSeconds: number;
    readonly idleExtensionSeconds: number;
    readonly rotateAfterSeconds: number;
    readonly maxConcurrent: number;
    readonly cookieName: string;
    readonly cookieSecure: boolean;
  };

  readonly password: {
    readonly minLength: number;
    readonly memoryCostKib: number;
    readonly timeCost: number;
    readonly parallelism: number;
    readonly maxFailedAttempts: number;
    readonly lockSeconds: number;
  };

  readonly risk: {
    readonly thresholds: {
      readonly monitor: number;
      readonly rateLimit: number;
      readonly challenge: number;
      readonly restrict: number;
      readonly block: number;
    };
    readonly allowedUserAgents: readonly string[];
  };

  readonly mail: {
    readonly driver: Env['MAIL_DRIVER'];
    readonly host: string;
    readonly port: number;
    readonly from: string;
    readonly username: string | undefined;
    readonly password: string | undefined;
    readonly timeoutSeconds: number;
  };

  readonly observability: {
    readonly logLevel: Env['LOG_LEVEL'];
    readonly logPretty: boolean;
  };

  readonly features: {
    readonly allowEnvOverrides: boolean;
  };
}

/**
 * Validates the environment and builds the typed configuration.
 *
 * Called exactly once, at startup. A missing or malformed variable stops the
 * process with a list of every problem, not just the first — starting a
 * container three times to discover three typos is a waste of everyone's day.
 */
export function loadConfig(source: NodeJS.ProcessEnv = process.env): Config {
  const parsed = envSchema.safeParse(source);

  if (!parsed.success) {
    const problems = parsed.error.issues
      .map((issue) => `  ${issue.path.join('.')}: ${issue.message}`)
      .join('\n');

    throw new Error(`Invalid environment configuration:\n${problems}\n\nSee .env.example.`);
  }

  const env = parsed.data;

  return Object.freeze({
    env: env.NODE_ENV,
    isProduction: env.NODE_ENV === 'production',
    isTest: env.NODE_ENV === 'test',
    debug: env.APP_DEBUG,

    app: Object.freeze({
      name: env.APP_NAME,
      version: env.APP_VERSION,
      url: env.APP_URL,
      webUrl: env.WEB_URL,
      host: env.API_HOST,
      port: env.API_PORT,
    }),

    database: Object.freeze({
      url: env.DATABASE_URL,
      poolMax: env.DATABASE_POOL_MAX,
      connectTimeoutSeconds: env.DATABASE_CONNECT_TIMEOUT_SECONDS,
      idleTimeoutSeconds: env.DATABASE_IDLE_TIMEOUT_SECONDS,
      logQueries: env.DATABASE_LOG_QUERIES,
    }),

    security: Object.freeze({
      appSecret: env.APP_SECRET,
      trustedProxies: Object.freeze(env.TRUSTED_PROXIES),
      corsAllowedOrigins: Object.freeze(env.CORS_ALLOWED_ORIGINS),
      hsts: env.SECURITY_HSTS,
    }),

    session: Object.freeze({
      absoluteLifetimeSeconds: env.SESSION_ABSOLUTE_LIFETIME_SECONDS,
      idleExtensionSeconds: env.SESSION_IDLE_EXTENSION_SECONDS,
      rotateAfterSeconds: env.SESSION_ROTATE_AFTER_SECONDS,
      maxConcurrent: env.SESSION_MAX_CONCURRENT,
      cookieName: env.SESSION_COOKIE_NAME,
      cookieSecure: env.SESSION_COOKIE_SECURE,
    }),

    password: Object.freeze({
      minLength: env.PASSWORD_MIN_LENGTH,
      memoryCostKib: env.ARGON2_MEMORY_COST_KIB,
      timeCost: env.ARGON2_TIME_COST,
      parallelism: env.ARGON2_PARALLELISM,
      maxFailedAttempts: env.AUTH_MAX_FAILED_ATTEMPTS,
      lockSeconds: env.AUTH_LOCK_SECONDS,
    }),

    risk: Object.freeze({
      thresholds: Object.freeze({
        monitor: env.RISK_THRESHOLD_MONITOR,
        rateLimit: env.RISK_THRESHOLD_RATE_LIMIT,
        challenge: env.RISK_THRESHOLD_CHALLENGE,
        restrict: env.RISK_THRESHOLD_RESTRICT,
        block: env.RISK_THRESHOLD_BLOCK,
      }),
      allowedUserAgents: Object.freeze(env.RISK_ALLOWED_USER_AGENTS),
    }),

    mail: Object.freeze({
      driver: env.MAIL_DRIVER,
      host: env.MAIL_HOST,
      port: env.MAIL_PORT,
      from: env.MAIL_FROM,
      username: env.MAIL_USERNAME,
      password: env.MAIL_PASSWORD,
      timeoutSeconds: env.MAIL_TIMEOUT_SECONDS,
    }),

    observability: Object.freeze({
      logLevel: env.LOG_LEVEL,
      logPretty: env.LOG_PRETTY,
    }),

    features: Object.freeze({
      allowEnvOverrides: env.FEATURE_ENV_OVERRIDES,
    }),
  });
}
