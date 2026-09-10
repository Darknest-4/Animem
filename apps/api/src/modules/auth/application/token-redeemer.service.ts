import { BadRequestError } from '@yume/core';
import { TokenGenerator } from '@yume/security';

import type { Clock } from '@yume/core';
import type { UserRepository } from '../../users/domain/user.repository.js';
import type { User } from '../../users/domain/user.types.js';
import type { OneTimeTokenRepository } from '../domain/auth.repository.js';
import {
  isTokenUsable,
  type OneTimeToken,
  type TokenPurpose,
} from '../domain/one-time-token.types.js';

export interface RedeemedToken {
  readonly token: OneTimeToken;
  readonly user: User;
}

export interface TokenRedeemerDependencies {
  readonly tokens: OneTimeTokenRepository;
  readonly users: UserRepository;
  readonly clock: Clock;
}

/**
 * Validates a mailed link and resolves who it belongs to.
 *
 * Email verification and password reset need exactly the same six checks, and
 * writing them twice is how one copy ends up missing the expiry test. The
 * failure message is identical in every case on purpose: distinguishing
 * "expired" from "already used" from "never existed" tells someone holding a
 * leaked link which one they are holding.
 */
export class TokenRedeemer {
  constructor(private readonly deps: TokenRedeemerDependencies) {}

  async redeem(purpose: TokenPurpose, presented: string): Promise<RedeemedToken> {
    const found = await this.deps.tokens.findByHash(purpose, TokenGenerator.hash(presented));

    if (found === null || !isTokenUsable(found, this.deps.clock.now())) {
      throw new BadRequestError(
        'This link is no longer valid. Request a new one.',
        'auth.invalid_token',
      );
    }

    const user = await this.deps.users.findById(found.userId);

    if (user === null) {
      throw new BadRequestError(
        'This link is no longer valid. Request a new one.',
        'auth.invalid_token',
      );
    }

    return { token: found, user };
  }
}
