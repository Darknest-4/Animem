import { inArray } from 'drizzle-orm';

import type { Database } from '../client.js';
import { featureFlags, permissions, rolePermissions, roles } from '../schema/index.js';
import { FEATURE_FLAG_SEED } from './feature-flags.seed.js';
import { PERMISSION_SEED } from './permissions.seed.js';
import { ROLE_SEED } from './roles.seed.js';

export interface SeedResult {
  readonly permissions: number;
  readonly roles: number;
  readonly grants: number;
  readonly featureFlags: number;
}

/**
 * Installs the baseline permissions, roles and flags.
 *
 * Idempotent throughout, so it is safe on every deploy and new entries can be
 * added to the seed files without forking behaviour between environments. It
 * deliberately never *removes* a grant: taking a permission away from a role in
 * production is an operator decision, not something a deploy should do quietly.
 */
export async function seed(db: Database): Promise<SeedResult> {
  return db.transaction(async (tx) => {
    await tx
      .insert(permissions)
      .values(PERMISSION_SEED.map((entry) => ({ ...entry })))
      .onConflictDoNothing({ target: permissions.slug });

    await tx
      .insert(roles)
      .values(
        ROLE_SEED.map((entry) => ({
          slug: entry.slug,
          name: entry.name,
          description: entry.description,
          isSystem: entry.isSystem,
        })),
      )
      .onConflictDoNothing({ target: roles.slug });

    // Resolve every slug to an id in two queries rather than one per grant.
    const roleRows = await tx
      .select({ id: roles.id, slug: roles.slug })
      .from(roles)
      .where(inArray(roles.slug, ROLE_SEED.map((role) => role.slug)));

    const permissionRows = await tx
      .select({ id: permissions.id, slug: permissions.slug })
      .from(permissions);

    const roleIdBySlug = new Map(roleRows.map((row) => [row.slug, row.id]));
    const permissionIdBySlug = new Map(permissionRows.map((row) => [row.slug, row.id]));

    const grantRows: { roleId: number; permissionId: number }[] = [];

    for (const role of ROLE_SEED) {
      const roleId = roleIdBySlug.get(role.slug);

      if (roleId === undefined) {
        throw new Error(`Seeded role "${role.slug}" is missing after insert.`);
      }

      for (const slug of role.permissions) {
        const permissionId = permissionIdBySlug.get(slug);

        if (permissionId === undefined) {
          // A grant naming a permission that does not exist would leave the role
          // quietly short of access. Fail the seed instead.
          throw new Error(`Role "${role.slug}" grants unknown permission "${slug}".`);
        }

        grantRows.push({ roleId, permissionId });
      }
    }

    const inserted =
      grantRows.length === 0
        ? []
        : await tx
            .insert(rolePermissions)
            .values(grantRows)
            .onConflictDoNothing()
            .returning({ roleId: rolePermissions.roleId });

    const grants = inserted.length;

    await tx
      .insert(featureFlags)
      .values(FEATURE_FLAG_SEED.map((entry) => ({ ...entry })))
      .onConflictDoNothing({ target: featureFlags.flagKey });

    return {
      permissions: PERMISSION_SEED.length,
      roles: ROLE_SEED.length,
      grants,
      featureFlags: FEATURE_FLAG_SEED.length,
    };
  });
}
