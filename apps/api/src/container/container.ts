import { randomBytes } from 'node:crypto';

import type { Config } from '@yume/config';
import { systemClock, type Clock } from '@yume/core';
import { createDatabase } from '@yume/db';
import type { Logger } from '@yume/logger';
import { CsrfTokenManager, PasswordHasher, PasswordPolicy, TokenGenerator } from '@yume/security';

import type { AuthDependencies } from '../modules/auth/application/auth.dependencies.js';
import { createAuthUseCases } from '../modules/auth/application/auth.usecases.js';
import { TokenIssuer } from '../modules/auth/application/token-issuer.service.js';
import { TokenRedeemer } from '../modules/auth/application/token-redeemer.service.js';
import { SessionCookie } from '../modules/auth/infrastructure/session-cookie.js';
import { FeatureFlagService } from '../modules/features/application/feature-flag.service.js';
import { DrizzleFeatureFlagRepository } from '../modules/features/infrastructure/drizzle-feature-flag.repository.js';
import { createMailer } from '../modules/mail/infrastructure/mailer.factory.js';
import { RateLimiter } from '../modules/security/application/rate-limiter.service.js';
import { DrizzleRateLimitRepository } from '../modules/security/infrastructure/drizzle-rate-limit.repository.js';
import { TrustedProxyResolver } from '../shared/network/trusted-proxy.js';
import { buildRepositories } from './repositories.factory.js';
import type { Services } from './services.js';
import { DrizzleUnitOfWork } from './unit-of-work.js';

export interface ContainerOptions {
  readonly config: Config;
  readonly logger: Logger;
  /** Overridden in tests with a fixed clock so expiry logic is deterministic. */
  readonly clock?: Clock;
}

/**
 * The composition root.
 *
 * The only place in the application where a concrete class is chosen and wired.
 * Every layer above depends on interfaces, which is what makes the dependency
 * arrows point inwards: nothing in a use case knows that Drizzle, pino,
 * nodemailer or Argon2 exist, and swapping any of them is an edit to this file.
 *
 * It is deliberately not a DI framework. A framework buys lazy resolution and
 * decorators, and costs a runtime graph that fails at request time instead of at
 * `tsc`. Here a missing dependency is a compile error.
 */
export async function createContainer(options: ContainerOptions): Promise<Services> {
  const { config, logger } = options;
  const clock = options.clock ?? systemClock;

  const database = createDatabase({
    url: config.database.url,
    poolMax: config.database.poolMax,
    connectTimeoutSeconds: config.database.connectTimeoutSeconds,
    idleTimeoutSeconds: config.database.idleTimeoutSeconds,
    logQueries: config.database.logQueries,
    logger: logger.child({ component: 'database' }),
  });

  const repositories = buildRepositories(database.db);
  const unitOfWork = new DrizzleUnitOfWork(database.db);

  const hasher = new PasswordHasher({
    memoryCostKib: config.password.memoryCostKib,
    timeCost: config.password.timeCost,
    parallelism: config.password.parallelism,
  });
  const passwordPolicy = new PasswordPolicy({ minLength: config.password.minLength });
  const tokenGenerator = new TokenGenerator();
  const csrf = new CsrfTokenManager(config.security.appSecret);
  const mailer = createMailer(config, logger.child({ component: 'mail' }));

  const tokenIssuer = new TokenIssuer({
    tokens: repositories.tokens,
    mailer,
    tokenGenerator,
    clock,
    config,
    logger: logger.child({ component: 'tokens' }),
  });

  const tokenRedeemer = new TokenRedeemer({
    tokens: repositories.tokens,
    users: repositories.users,
    clock,
  });

  const authDependencies: AuthDependencies = {
    repositories,
    unitOfWork,
    hasher,
    passwordPolicy,
    tokenGenerator,
    csrf,
    tokenIssuer,
    tokenRedeemer,
    mailer,
    clock,
    logger: logger.child({ component: 'auth' }),
    config,
    // Hashing a throwaway passphrase with the live parameters, once, at startup.
    // Sign-in verifies against it when no account matches, so "no such user"
    // costs the same as "wrong password" and the response time stops being an
    // account-enumeration oracle.
    timingEqualiserHash: await hasher.hash(randomBytes(32).toString('base64url')),
  };

  const featureFlags = new FeatureFlagService(
    new DrizzleFeatureFlagRepository(database.db),
    clock,
    logger.child({ component: 'features' }),
    config.features.overrides,
  );

  return Object.freeze({
    config,
    logger,
    clock,
    database,
    repositories,
    unitOfWork,
    auth: createAuthUseCases(authDependencies),
    sessionCookie: new SessionCookie(config),
    csrf,
    rateLimiter: new RateLimiter(
      new DrizzleRateLimitRepository(database.db),
      clock,
      logger.child({ component: 'rate-limit' }),
    ),
    featureFlags,
    trustedProxies: new TrustedProxyResolver(config.security.trustedProxies),
    async close(): Promise<void> {
      await database.close();
    },
  });
}
