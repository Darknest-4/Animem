import { ForbiddenError, UnauthorizedError } from '@yume/core';

import type { AuthContext } from '../../../http/context.js';
import { PermissionSet } from '../domain/permission.js';

/**
 * Permission checks against an already-resolved request identity.
 *
 * The effective set is loaded exactly once per request, when the session is
 * authenticated, and every check reads that snapshot. There is no service
 * holding a cache: a process-wide memo of "what may this user do" goes stale the
 * moment a role is revoked, and the request that most needs the fresh answer is
 * the one right after an administrator takes a permission away.
 *
 * Templates may use these to hide a control. Hiding is never the defence — the
 * route's declared `access` is enforced before any handler runs. The site this
 * replaces checked permissions in views only, which meant every admin action was
 * reachable by typing its URL.
 */
export function permissionsOf(auth: AuthContext): PermissionSet {
  return PermissionSet.from(auth.permissions);
}

export function can(auth: AuthContext, permission: string): boolean {
  return permissionsOf(auth).allows(permission);
}

export function canAll(auth: AuthContext, permissions: readonly string[]): boolean {
  return permissionsOf(auth).allowsAll(permissions);
}

/**
 * @throws UnauthorizedError when nobody is signed in and the permission is not
 * one the `guest` role holds — a 401 tells the client to sign in, where a 403
 * would tell them to give up.
 * @throws ForbiddenError when a known identity simply may not.
 */
export function assertCan(auth: AuthContext, permission: string): void {
  if (can(auth, permission)) {
    return;
  }

  if (!auth.isAuthenticated) {
    throw new UnauthorizedError('You need to sign in to do this.', 'auth.required', {
      requiredPermission: permission,
    });
  }

  throw new ForbiddenError(
    'You do not have permission to perform this action.',
    'authorization.denied',
    { requiredPermission: permission },
  );
}
