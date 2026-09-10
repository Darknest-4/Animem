import { and, count, desc, eq, or, sql } from 'drizzle-orm';

import type { PageRequest } from '@yume/core';
import { toLimitOffset, type Executor, schema } from '@yume/db';

import { canonicaliseEmail, canonicaliseUsername } from '../../../shared/index.js';
import type { UserRepository } from '../domain/user.repository.js';
import { UserId, type User, type UserStatus } from '../domain/user.types.js';

/**
 * The role slugs a user holds, as an array-aggregated subquery.
 *
 * The correlation is written `"users"."id"` rather than interpolating the column
 * object, which Drizzle renders unqualified as `"id"`. Unqualified, it binds to
 * `roles.id` from this subquery's own FROM clause instead of the outer row — an
 * inner scope always wins in SQL. Here the types disagree so it fails loudly;
 * had `roles.id` been a uuid it would have returned confidently wrong role sets,
 * which for a permission lookup is the worst possible failure mode.
 */
const roleSlugs = sql<string[]>`
  COALESCE(
    (SELECT array_agg(r.slug ORDER BY r.slug)
     FROM ${schema.userRoles} ur
     JOIN ${schema.roles} r ON r.id = ur.role_id
     WHERE ur.user_id = ${schema.users}.${sql.identifier('id')}),
    ARRAY[]::varchar[]
  )
`;

const selection = {
  id: schema.users.id,
  username: schema.users.username,
  email: schema.users.email,
  status: schema.users.status,
  emailVerifiedAt: schema.users.emailVerifiedAt,
  createdAt: schema.users.createdAt,
  updatedAt: schema.users.updatedAt,
  roles: roleSlugs,
};

interface Row {
  id: string;
  username: string;
  email: string;
  status: UserStatus;
  emailVerifiedAt: Date | null;
  createdAt: Date;
  updatedAt: Date;
  roles: string[] | null;
}

function toUser(row: Row): User {
  return {
    id: UserId.of(row.id),
    username: row.username,
    email: row.email,
    status: row.status,
    emailVerifiedAt: row.emailVerifiedAt,
    roles: row.roles ?? [],
    createdAt: row.createdAt,
    updatedAt: row.updatedAt,
  };
}

export class DrizzleUserRepository implements UserRepository {
  constructor(private readonly db: Executor) {}

  /** Rebinds the repository to an open transaction, for composed writes. */
  withExecutor(executor: Executor): DrizzleUserRepository {
    return new DrizzleUserRepository(executor);
  }

  async findById(id: UserId): Promise<User | null> {
    const rows = await this.db.select(selection).from(schema.users).where(eq(schema.users.id, id)).limit(1);

    return rows[0] === undefined ? null : toUser(rows[0]);
  }

  async findByEmail(email: string): Promise<User | null> {
    const rows = await this.db
      .select(selection)
      .from(schema.users)
      .where(eq(schema.users.emailCanonical, canonicaliseEmail(email)))
      .limit(1);

    return rows[0] === undefined ? null : toUser(rows[0]);
  }

  async findByUsername(username: string): Promise<User | null> {
    const rows = await this.db
      .select(selection)
      .from(schema.users)
      .where(eq(schema.users.usernameCanonical, canonicaliseUsername(username)))
      .limit(1);

    return rows[0] === undefined ? null : toUser(rows[0]);
  }

  /**
   * One query for both shapes.
   *
   * Two separate lookups would take measurably different time depending on which
   * matched, turning the sign-in field into an oracle for which form of
   * identifier exists.
   */
  async findByIdentifier(identifier: string): Promise<User | null> {
    const trimmed = identifier.trim();

    if (trimmed === '') {
      return null;
    }

    const rows = await this.db
      .select(selection)
      .from(schema.users)
      .where(
        or(
          eq(schema.users.usernameCanonical, canonicaliseUsername(trimmed)),
          eq(schema.users.emailCanonical, canonicaliseEmail(trimmed)),
        ),
      )
      .limit(1);

    return rows[0] === undefined ? null : toUser(rows[0]);
  }

  async usernameExists(username: string): Promise<boolean> {
    const rows = await this.db
      .select({ one: sql<number>`1` })
      .from(schema.users)
      .where(eq(schema.users.usernameCanonical, canonicaliseUsername(username)))
      .limit(1);

    return rows.length > 0;
  }

  async emailExists(email: string): Promise<boolean> {
    const rows = await this.db
      .select({ one: sql<number>`1` })
      .from(schema.users)
      .where(eq(schema.users.emailCanonical, canonicaliseEmail(email)))
      .limit(1);

    return rows.length > 0;
  }

  async insert(user: User & { usernameCanonical: string; emailCanonical: string }): Promise<void> {
    await this.db.insert(schema.users).values({
      id: user.id,
      username: user.username,
      usernameCanonical: user.usernameCanonical,
      email: user.email,
      emailCanonical: user.emailCanonical,
      status: user.status,
      emailVerifiedAt: user.emailVerifiedAt,
      createdAt: user.createdAt,
      updatedAt: user.updatedAt,
    });
  }

  async update(user: User & { emailCanonical?: string }): Promise<void> {
    await this.db
      .update(schema.users)
      .set({
        username: user.username,
        usernameCanonical: canonicaliseUsername(user.username),
        email: user.email,
        emailCanonical: user.emailCanonical ?? canonicaliseEmail(user.email),
        status: user.status,
        emailVerifiedAt: user.emailVerifiedAt,
        updatedAt: user.updatedAt,
      })
      .where(eq(schema.users.id, user.id));
  }

  async paginate(request: PageRequest): Promise<{ items: readonly User[]; total: number }> {
    const { limit, offset } = toLimitOffset(request);

    const [rows, totals] = await Promise.all([
      this.db
        .select(selection)
        .from(schema.users)
        .orderBy(desc(schema.users.createdAt))
        .limit(limit)
        .offset(offset),
      this.db.select({ value: count() }).from(schema.users),
    ]);

    return { items: rows.map(toUser), total: totals[0]?.value ?? 0 };
  }

  async countWithRole(roleSlug: string): Promise<number> {
    const rows = await this.db
      .select({ value: count() })
      .from(schema.userRoles)
      .innerJoin(schema.roles, eq(schema.roles.id, schema.userRoles.roleId))
      .where(and(eq(schema.roles.slug, roleSlug)));

    return rows[0]?.value ?? 0;
  }
}
