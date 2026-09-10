import type { Clock } from '@yume/core';
import type { Config } from '@yume/config';
import type { Logger } from '@yume/logger';
import type { PasswordHasher, PasswordPolicy, TokenGenerator } from '@yume/security';

import type { Repositories, UnitOfWork } from '../../shared/repositories.js';

/**
 * Everything the auth use cases need.
 *
 * Declared as one interface so each use case takes a single constructor
 * argument instead of eleven, and so adding a dependency is one edit rather than
 * one per use case. The composition root builds it once.
 */
export interface AuthDependencies {
  /** Repositories bound to the connection pool, for reads and single writes. */
  readonly repositories: Repositories;
  /** Opens a transaction with every repository rebound to it. */
  readonly unitOfWork: UnitOfWork;
  readonly hasher: PasswordHasher;
  readonly passwordPolicy: PasswordPolicy;
  readonly tokenGenerator: TokenGenerator;
  readonly clock: Clock;
  readonly logger: Logger;
  readonly config: Config;
  /**
   * An Argon2id hash of a random passphrase, produced once at startup with the
   * configured parameters.
   *
   * Verified on the unknown-user branch of sign-in so that "no such account"
   * costs the same wall-clock time as "wrong password". Generated rather than
   * hardcoded so it can never drift from the live cost parameters — a constant
   * baked at a lower cost would make the unknown-user path measurably faster and
   * hand an attacker back the oracle this is meant to close.
   */
  readonly timingEqualiserHash: string;
}
