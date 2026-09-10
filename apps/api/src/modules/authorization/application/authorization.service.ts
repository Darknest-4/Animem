import { ForbiddenError } from '@yume/core';

import type { UserId } from '../../users/domain/user.types.js';
import type { PermissionSet } from '../domain/permission.js';
import type { RoleRepository } from '../domain/role.repository.js';

/**
 * The single place that answers "may this actor do this?".
 *
 * Templates may ask it to hide a button, but hiding is never the defence: the
 * route's declared permission is enforced before any handler runs. The legacy
 * `perm()` function was called from views only, which meant every admin action
 * was reachable by typing its URL.
 */
export class AuthorizationService {
  /** Memoised per request, so eight checks on one page cost one query. */
  private readonly cache = new Map<string, PermissionSet>();

  constructor(private readonly roles: RoleRepository) {}

  async permissionsFor(userId: UserId | null): Promise<PermissionSet> {
    const key = userId ?? '@guest';
    const cached = this.cache.get(key);

    if (cached !== undefined) {
      return cached;
    }

    const permissions = userId === null
      ? await this.roles.permissionsForGuest()
      : await this.roles.permissionsForUser(userId);

    this.cache.set(key, permissions);

    return permissions;
  }

  async allows(userId: UserId | null, permission: string): Promise<boolean> {
    return (await this.permissionsFor(userId)).allows(permission);
  }

  async allowsAll(userId: UserId | null, permissions: readonly string[]): Promise<boolean> {
    return (await this.permissionsFor(userId)).allowsAll(permissions);
  }

  /** @throws ForbiddenError */
  async assert(userId: UserId | null, permission: string): Promise<void> {
    if (!(await this.allows(userId, permission))) {
      throw new ForbiddenError(
        'You do not have permission to perform this action.',
        'authorization.denied',
        { requiredPermission: permission },
      );
    }
  }

  forget(userId: UserId): void {
    this.cache.delete(userId);
  }
}
