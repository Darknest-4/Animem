import { ConflictError, DomainRuleError } from '@yume/core';
import type { AuthenticatedUser } from '@yume/contracts';
import { translateUniqueViolation } from '@yume/db';

import { createUser } from '../../users/domain/user.entity.js';
import { createCredential } from '../domain/credential.entity.js';
import type { AuthDependencies } from './auth.dependencies.js';
import { toAuthenticatedUser } from './authenticated-user.view.js';

export interface RegisterCommand {
  readonly username: string;
  readonly email: string;
  readonly password: string;
  readonly ip: string;
  readonly userAgent: string;
}

/**
 * Registration — a use case the site this replaces never had at all.
 *
 * The user row, the credential row and the default role are written in one
 * transaction: a half-registered account that can never sign in is worse than a
 * failed registration.
 */
export class RegisterUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: RegisterCommand): Promise<AuthenticatedUser> {
    const { deps } = this;
    const now = deps.clock.now();

    deps.passwordPolicy.assertAcceptable(command.password, [command.username, command.email]);

    // Checked up front for a readable error. The unique indexes are what
    // actually hold when two registrations race.
    if (await deps.repositories.users.usernameExists(command.username)) {
      throw new ConflictError('That username is already taken.', 'user.username_taken');
    }

    if (await deps.repositories.users.emailExists(command.email)) {
      throw new ConflictError('That email address is already registered.', 'user.email_taken');
    }

    const user = createUser({
      username: command.username,
      email: command.email,
      now,
      roles: ['user'],
      requiresEmailVerification: true,
    });

    const passwordHash = await deps.hasher.hash(command.password);

    try {
      // All three writes are bound to one transaction, so a failure on the
      // third does not leave an account that can never sign in.
      await deps.unitOfWork.run(async (repos) => {
        await repos.users.insert(user);
        await repos.credentials.insert(createCredential(user.id, passwordHash, now));
        await repos.roles.assignRole(user.id, 'user', null);
      });
    } catch (error) {
      translateUniqueViolation(error, {
        users_username_canonical_key: {
          message: 'That username is already taken.',
          code: 'user.username_taken',
        },
        users_email_canonical_key: {
          message: 'That email address is already registered.',
          code: 'user.email_taken',
        },
      });
    }

    deps.logger.info('User registered.', { userId: user.id, ip: command.ip });

    const permissions = await deps.repositories.roles.permissionsForUser(user.id);

    if (permissions.size === 0) {
      throw new DomainRuleError('Registration completed without any role.', 'user.no_role');
    }

    return toAuthenticatedUser(user, permissions.toArray());
  }
}
