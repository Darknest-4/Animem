import { relations, sql } from 'drizzle-orm';
import {
  boolean,
  index,
  integer,
  pgTable,
  primaryKey,
  serial,
  text,
  uniqueIndex,
  uuid,
  varchar,
} from 'drizzle-orm/pg-core';

import { createdAt } from './columns.js';
import { users } from './users.schema.js';

/**
 * Role-based access control.
 *
 * The legacy schema had the right shape — user to group to permission — but the
 * lookup keyed off a forgeable cookie and the check only ever ran inside
 * templates. The data model survives; the enforcement moves to the routing
 * layer, where it cannot be bypassed by typing the URL.
 */
export const permissions = pgTable(
  'permissions',
  {
    id: serial('id').primaryKey(),
    /** `resource.action`, `resource.*` or `*`. */
    slug: varchar('slug', { length: 100 }).notNull(),
    name: varchar('name', { length: 255 }).notNull(),
    description: text('description'),
    category: varchar('category', { length: 64 }).notNull().default('general'),
    createdAt: createdAt(),
  },
  (table) => [
    uniqueIndex('permissions_slug_key').on(table.slug),
    // The shape lives in the schema as well as in the value object, so a typo in
    // a seed file cannot create a permission no route can ever ask for.
    index('permissions_category_idx').on(table.category),
  ],
);

export const roles = pgTable(
  'roles',
  {
    id: serial('id').primaryKey(),
    slug: varchar('slug', { length: 64 }).notNull(),
    name: varchar('name', { length: 255 }).notNull(),
    description: text('description'),
    /** Referenced by code (guest, user, admin); cannot be deleted or renamed. */
    isSystem: boolean('is_system').notNull().default(false),
    createdAt: createdAt(),
  },
  (table) => [uniqueIndex('roles_slug_key').on(table.slug)],
);

export const rolePermissions = pgTable(
  'role_permissions',
  {
    roleId: integer('role_id')
      .notNull()
      .references(() => roles.id, { onDelete: 'cascade' }),
    permissionId: integer('permission_id')
      .notNull()
      .references(() => permissions.id, { onDelete: 'cascade' }),
    grantedAt: createdAt(),
  },
  (table) => [
    primaryKey({ columns: [table.roleId, table.permissionId] }),
    index('role_permissions_permission_idx').on(table.permissionId),
  ],
);

export const userRoles = pgTable(
  'user_roles',
  {
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    roleId: integer('role_id')
      .notNull()
      .references(() => roles.id, { onDelete: 'cascade' }),
    grantedAt: createdAt(),
    /** Who granted it. The first question asked after an incident. */
    grantedBy: uuid('granted_by').references(() => users.id, { onDelete: 'set null' }),
  },
  (table) => [
    primaryKey({ columns: [table.userId, table.roleId] }),
    index('user_roles_role_idx').on(table.roleId),
  ],
);

export const rolesRelations = relations(roles, ({ many }) => ({
  rolePermissions: many(rolePermissions),
  userRoles: many(userRoles),
}));

export const permissionsRelations = relations(permissions, ({ many }) => ({
  rolePermissions: many(rolePermissions),
}));

export const rolePermissionsRelations = relations(rolePermissions, ({ one }) => ({
  role: one(roles, { fields: [rolePermissions.roleId], references: [roles.id] }),
  permission: one(permissions, {
    fields: [rolePermissions.permissionId],
    references: [permissions.id],
  }),
}));

export const userRolesRelations = relations(userRoles, ({ one }) => ({
  user: one(users, { fields: [userRoles.userId], references: [users.id] }),
  role: one(roles, { fields: [userRoles.roleId], references: [roles.id] }),
}));

/** The SQL that enforces the permission slug shape, applied by migration. */
export const PERMISSION_SLUG_CHECK = sql`slug ~ '^(\\*|[a-z][a-z0-9_]*\\.(\\*|[a-z][a-z0-9_]*))$'`;
