import type { UserId } from '../../users/domain/user.types.js';
import type { PermissionSet } from './permission.js';

export interface Role {
  readonly slug: string;
  readonly name: string;
  readonly isSystem: boolean;
  readonly permissions: PermissionSet;
}

export interface RoleRepository {
  findBySlug(slug: string): Promise<Role | null>;
  all(): Promise<readonly Role[]>;

  /** Effective permissions for a signed-in user, flattened across their roles. */
  permissionsForUser(userId: UserId): Promise<PermissionSet>;
  /** Effective permissions for an anonymous visitor: the `guest` role. */
  permissionsForGuest(): Promise<PermissionSet>;

  roleSlugsForUser(userId: UserId): Promise<readonly string[]>;
  assignRole(userId: UserId, roleSlug: string, grantedBy: UserId | null): Promise<void>;
  revokeRole(userId: UserId, roleSlug: string): Promise<void>;
  countUsersWithRole(roleSlug: string): Promise<number>;
}
