import type { Executor } from '@yume/db';

import { DrizzleCredentialRepository } from '../modules/auth/infrastructure/drizzle-credential.repository.js';
import { DrizzleOneTimeTokenRepository } from '../modules/auth/infrastructure/drizzle-one-time-token.repository.js';
import { DrizzleSessionRepository } from '../modules/auth/infrastructure/drizzle-session.repository.js';
import { DrizzleRoleRepository } from '../modules/authorization/infrastructure/drizzle-role.repository.js';
import type { Repositories } from '../modules/shared/repositories.js';
import { DrizzleUserRepository } from '../modules/users/infrastructure/drizzle-user.repository.js';

/**
 * Binds every repository to one executor.
 *
 * The single place that names a concrete implementation, so swapping a
 * repository is one line here and nothing anywhere else. Taking the executor as
 * an argument is what lets the unit of work rebuild the whole set against an
 * open transaction — with a hardcoded pool handle, a "transaction" would silently
 * write outside itself.
 */
export function buildRepositories(executor: Executor): Repositories {
  return Object.freeze({
    users: new DrizzleUserRepository(executor),
    credentials: new DrizzleCredentialRepository(executor),
    sessions: new DrizzleSessionRepository(executor),
    tokens: new DrizzleOneTimeTokenRepository(executor),
    roles: new DrizzleRoleRepository(executor),
  });
}
