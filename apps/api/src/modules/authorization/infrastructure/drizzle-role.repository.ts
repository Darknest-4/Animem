import { and, count, eq, inArray, sql } from 'drizzle-orm';

import { DomainRuleError } from '@yume/core';
import { schema, type Executor } from '@yume/db';

import type { UserId } from '../../users/domain/user.types.js';
import { PermissionSet } from '../domain/permission.js';
import type { Role, RoleRepository } from '../domain/role.repository.js';

export class DrizzleRoleRepository implements RoleRepository {
  constructor(private readonly db: Executor) {}

  withExecutor(executor: Executor): DrizzleRoleRepository {
    return new DrizzleRoleRepository(executor);
  }

  async findBySlug(slug: string): Promise<Role | null> {
    const rows = await this.db
      .select({
        slug: schema.roles.slug,
        name: schema.roles.name,
        isSystem: schema.roles.isSystem,
        permission: schema.permissions.slug,
      })
      .from(schema.roles)
      .leftJoin(schema.rolePermissions, eq(schema.rolePermissions.roleId, schema.roles.id))
      .leftJoin(schema.permissions, eq(schema.permissions.id, schema.rolePermissions.permissionId))
      .where(eq(schema.roles.slug, slug));

    const first = rows[0];

    if (first === undefined) {
      return null;
    }

    return {
      slug: first.slug,
      name: first.name,
      isSystem: first.isSystem,
      permissions: PermissionSet.from(
        rows.map((row) => row.permission).filter((value): value is string => value !== null),
      ),
    };
  }

  async all(): Promise<readonly Role[]> {
    const rows = await this.db
      .select({
        slug: schema.roles.slug,
        name: schema.roles.name,
        isSystem: schema.roles.isSystem,
        permission: schema.permissions.slug,
      })
      .from(schema.roles)
      .leftJoin(schema.rolePermissions, eq(schema.rolePermissions.roleId, schema.roles.id))
      .leftJoin(schema.permissions, eq(schema.permissions.id, schema.rolePermissions.permissionId))
      .orderBy(schema.roles.slug);

    const grouped = new Map<string, { name: string; isSystem: boolean; permissions: string[] }>();

    for (const row of rows) {
      const existing = grouped.get(row.slug) ?? { name: row.name, isSystem: row.isSystem, permissions: [] };

      if (row.permission !== null) {
        existing.permissions.push(row.permission);
      }

      grouped.set(row.slug, existing);
    }

    return [...grouped.entries()].map(([slug, value]) => ({
      slug,
      name: value.name,
      isSystem: value.isSystem,
      permissions: PermissionSet.from(value.permissions),
    }));
  }

  /**
   * One flattening join, not the legacy `perm()` function's loop of three
   * nested queries per permission checked.
   */
  async permissionsForUser(userId: UserId): Promise<PermissionSet> {
    const rows = await this.db
      .selectDistinct({ slug: schema.permissions.slug })
      .from(schema.userRoles)
      .innerJoin(schema.rolePermissions, eq(schema.rolePermissions.roleId, schema.userRoles.roleId))
      .innerJoin(schema.permissions, eq(schema.permissions.id, schema.rolePermissions.permissionId))
      .where(eq(schema.userRoles.userId, userId));

    return PermissionSet.from(rows.map((row) => row.slug));
  }

  async permissionsForGuest(): Promise<PermissionSet> {
    const rows = await this.db
      .select({ slug: schema.permissions.slug })
      .from(schema.roles)
      .innerJoin(schema.rolePermissions, eq(schema.rolePermissions.roleId, schema.roles.id))
      .innerJoin(schema.permissions, eq(schema.permissions.id, schema.rolePermissions.permissionId))
      .where(eq(schema.roles.slug, 'guest'));

    return PermissionSet.from(rows.map((row) => row.slug));
  }

  async roleSlugsForUser(userId: UserId): Promise<readonly string[]> {
    const rows = await this.db
      .select({ slug: schema.roles.slug })
      .from(schema.userRoles)
      .innerJoin(schema.roles, eq(schema.roles.id, schema.userRoles.roleId))
      .where(eq(schema.userRoles.userId, userId))
      .orderBy(schema.roles.slug);

    return rows.map((row) => row.slug);
  }

  async assignRole(userId: UserId, roleSlug: string, grantedBy: UserId | null): Promise<void> {
    const roles = await this.db
      .select({ id: schema.roles.id })
      .from(schema.roles)
      .where(eq(schema.roles.slug, roleSlug))
      .limit(1);

    const role = roles[0];

    if (role === undefined) {
      // A silent no-op would leave the account with no access at all, which is
      // exactly the kind of failure that should be loud.
      throw new DomainRuleError(`Role "${roleSlug}" does not exist.`, 'role.not_found');
    }

    await this.db
      .insert(schema.userRoles)
      .values({ userId, roleId: role.id, grantedBy })
      .onConflictDoNothing();
  }

  async revokeRole(userId: UserId, roleSlug: string): Promise<void> {
    const roleId = this.db
      .select({ id: schema.roles.id })
      .from(schema.roles)
      .where(eq(schema.roles.slug, roleSlug));

    await this.db
      .delete(schema.userRoles)
      .where(and(eq(schema.userRoles.userId, userId), inArray(schema.userRoles.roleId, roleId)));
  }

  async countUsersWithRole(roleSlug: string): Promise<number> {
    const rows = await this.db
      .select({ value: count() })
      .from(schema.userRoles)
      .innerJoin(schema.roles, eq(schema.roles.id, schema.userRoles.roleId))
      .where(eq(schema.roles.slug, roleSlug));

    return rows[0]?.value ?? 0;
  }

  /** Used by the seed check in tests: every permission slug currently defined. */
  async allPermissionSlugs(): Promise<readonly string[]> {
    const rows = await this.db
      .select({ slug: schema.permissions.slug })
      .from(schema.permissions)
      .orderBy(sql`slug`);

    return rows.map((row) => row.slug);
  }
}
