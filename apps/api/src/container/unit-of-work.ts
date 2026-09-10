import type { Database } from '@yume/db';

import type { Repositories, UnitOfWork } from '../modules/shared/repositories.js';
import { buildRepositories } from './repositories.factory.js';

/**
 * The Drizzle-backed unit of work.
 *
 * Opens a transaction and hands the callback a *fresh* repository set bound to
 * it. That rebinding is the entire point: a use case that opens a transaction
 * and then calls a pool-bound repository writes outside the transaction, the
 * code reads as atomic, and the bug only appears under the failure that was
 * supposed to roll back — which is to say, in production.
 *
 * This class is the only thing in the codebase that knows that. Use cases see
 * the `UnitOfWork` port and nothing else.
 */
export class DrizzleUnitOfWork implements UnitOfWork {
  constructor(private readonly db: Database) {}

  async run<T>(work: (repositories: Repositories) => Promise<T>): Promise<T> {
    return this.db.transaction(async (tx) => work(buildRepositories(tx)));
  }
}
