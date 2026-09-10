import { ForbiddenError, LockedError, UnauthorizedError } from '@yume/core';
import type { LoginResponse } from '@yume/contracts';

import { canAuthenticate } from '../../users/domain/user.types.js';
import {
  recordFailure,
  recordSuccess,
  upgradeHash,
} from '../domain/credential.entity.js';
import { isLegacy, isLocked } from '../domain/credential.types.js';
import { revokeSession, startSession } from '../domain/session.entity.js';
import type { AuthDependencies } from './auth.dependencies.js';
import { toAuthenticatedUser } from './authenticated-user.view.js';

export interface LoginCommand {
  /** Username or email address, as typed. */
  readonly identifier: string;
  readonly password: string;
  readonly ip: string;
  readonly userAgent: string;
}

export interface LoginResult {
  /** The response body, exactly as the contract declares it. */
  readonly response: LoginResponse;
  /** What the HTTP layer needs to set the cookie, which the body cannot describe. */
  readonly token: string;
  readonly expiresAt: Date;
}

export class LoginUseCase {
  constructor(private readonly deps: AuthDependencies) {}

  async execute(command: LoginCommand): Promise<LoginResult> {
    const { deps } = this;
    const { repositories: repos } = deps;
    const now = deps.clock.now();

    const user = await repos.users.findByIdentifier(command.identifier);

    if (user === null) {
      await deps.hasher.verify(command.password, deps.timingEqualiserHash);
      throw new UnauthorizedError('Invalid username or password.', 'auth.invalid_credentials');
    }

    const credential = await repos.credentials.findForUser(user.id);

    if (credential === null) {
      await deps.hasher.verify(command.password, deps.timingEqualiserHash);
      throw new UnauthorizedError('Invalid username or password.', 'auth.invalid_credentials');
    }

    if (isLocked(credential, now)) {
      throw new LockedError(
        'Too many failed attempts. Try again later.',
        'auth.account_locked',
        Math.max(1, Math.ceil(((credential.lockedUntil?.getTime() ?? 0) - now.getTime()) / 1000)),
      );
    }

    const verified = isLegacy(credential)
      ? deps.hasher.verifyLegacySha256(command.password, credential.passwordHash)
      : await deps.hasher.verify(command.password, credential.passwordHash);

    if (!verified) {
      await repos.credentials.update(
        recordFailure(credential, now, deps.config.password.maxFailedAttempts, deps.config.password.lockSeconds),
      );

      throw new UnauthorizedError('Invalid username or password.', 'auth.invalid_credentials');
    }

    if (!canAuthenticate(user)) {
      throw new ForbiddenError('This account is not active.', 'auth.account_not_active');
    }

    // A legacy or under-parameterised hash is replaced here, on the one occasion
    // the plaintext is legitimately in hand. This is the whole migration: no
    // mass reset, no user action.
    const needsUpgrade = isLegacy(credential) || deps.hasher.needsRehash(credential.passwordHash);
    const upgraded = needsUpgrade
      ? upgradeHash(credential, await deps.hasher.hash(command.password))
      : credential;

    await repos.credentials.update(recordSuccess(upgraded));

    if (needsUpgrade) {
      deps.logger.info('Password hash upgraded on sign-in.', {
        userId: user.id,
        from: credential.algorithm,
      });
    }

    const token = deps.tokenGenerator.generate();
    const session = startSession({
      userId: user.id,
      tokenHash: token.hash,
      ip: command.ip,
      userAgent: command.userAgent,
      now,
      policy: deps.config.session,
    });

    await this.enforceConcurrentSessionCap(user.id, now);
    await repos.sessions.insert(session);

    const permissions = await repos.roles.permissionsForUser(user.id);

    deps.logger.info('Sign-in succeeded.', { userId: user.id, sessionId: session.id });

    return {
      response: {
        user: toAuthenticatedUser(user, permissions.toArray()),
        session: {
          id: session.id,
          expires_at: session.expiresAt.toISOString(),
          // Minted here rather than on a separate round trip, so the client has
          // everything it needs to make its first write immediately after
          // signing in.
          csrf_token: deps.csrf.generate(session.id),
        },
        token: token.plain,
      },
      token: token.plain,
      expiresAt: session.expiresAt,
    };
  }

  /** Revokes the oldest sessions once the cap is reached. */
  private async enforceConcurrentSessionCap(userId: Parameters<typeof startSession>[0]['userId'], now: Date): Promise<void> {
    const { repositories: repos, config } = this.deps;
    const active = await repos.sessions.findActiveForUser(userId, now);

    if (active.length < config.session.maxConcurrent) {
      return;
    }

    // findActiveForUser returns newest first, so the tail is the oldest.
    for (const stale of active.slice(config.session.maxConcurrent - 1)) {
      await repos.sessions.update(revokeSession(stale, now, 'concurrent_session_limit'));
    }
  }
}
