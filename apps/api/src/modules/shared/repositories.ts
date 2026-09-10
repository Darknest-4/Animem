import type {
  CredentialRepository,
  OneTimeTokenRepository,
  SessionRepository,
} from '../auth/domain/auth.repository.js';
import type { RoleRepository } from '../authorization/domain/role.repository.js';
import type { UserRepository } from '../users/domain/user.repository.js';

/**
 * Every repository, addressed by domain interface.
 *
 * Use cases depend on this and nothing else, so none of them knows Drizzle
 * exists. The concrete implementations are chosen once, in the composition root.
 */
export interface Repositories {
  readonly users: UserRepository;
  readonly credentials: CredentialRepository;
  readonly sessions: SessionRepository;
  readonly tokens: OneTimeTokenRepository;
  readonly roles: RoleRepository;
}

/**
 * Runs a callback atomically, handing it repositories bound to the transaction.
 *
 * This exists because the alternative is a real and quiet bug: a use case that
 * opens a transaction and then calls a repository built against the connection
 * pool writes *outside* it. The code reads as atomic and is not, and the symptom
 * only shows up under a failure that should have rolled back.
 *
 * The knowledge of how to rebind lives in the composition root, so the use case
 * sees only domain types.
 */
export interface UnitOfWork {
  run<T>(work: (repositories: Repositories) => Promise<T>): Promise<T>;
}
