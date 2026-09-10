import type { AuthenticatedUser } from '@yume/contracts';

import type { User } from '../../users/domain/user.types.js';

/**
 * The read model returned to a client.
 *
 * Never carries a hash or a token. Written once and used by every endpoint that
 * returns a user, so a field added here appears everywhere consistently instead
 * of in whichever serialiser someone remembered to update.
 */
export function toAuthenticatedUser(user: User, permissions: readonly string[]): AuthenticatedUser {
  return {
    id: user.id,
    username: user.username,
    email: user.email,
    status: user.status,
    email_verified: user.emailVerifiedAt !== null,
    roles: [...user.roles],
    permissions: [...permissions],
    created_at: user.createdAt.toISOString(),
  };
}
