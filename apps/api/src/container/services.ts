import type { Config } from '@yume/config';
import type { Clock } from '@yume/core';
import type { DatabaseHandle } from '@yume/db';
import type { Logger } from '@yume/logger';
import type { CsrfTokenManager } from '@yume/security';

import type { AuthUseCases } from '../modules/auth/application/auth.usecases.js';
import type { SessionCookie } from '../modules/auth/infrastructure/session-cookie.js';
import type { FeatureFlagService } from '../modules/features/application/feature-flag.service.js';
import type { RateLimiter } from '../modules/security/application/rate-limiter.service.js';
import type { Repositories, UnitOfWork } from '../modules/shared/repositories.js';
import type { TrustedProxyResolver } from '../shared/network/trusted-proxy.js';

/**
 * Everything the HTTP layer is allowed to reach.
 *
 * One explicit object rather than a service locator or a decorator soup: what a
 * route can use is visible in a type, so a handler cannot quietly acquire a
 * database handle and start writing SQL next to a use case.
 */
export interface Services {
  readonly config: Config;
  readonly logger: Logger;
  readonly clock: Clock;
  readonly database: DatabaseHandle;

  readonly repositories: Repositories;
  readonly unitOfWork: UnitOfWork;

  readonly auth: AuthUseCases;
  readonly sessionCookie: SessionCookie;
  readonly csrf: CsrfTokenManager;
  readonly rateLimiter: RateLimiter;
  readonly featureFlags: FeatureFlagService;
  readonly trustedProxies: TrustedProxyResolver;

  /** Releases the connection pool and any transport. Called once, on shutdown. */
  close(): Promise<void>;
}
