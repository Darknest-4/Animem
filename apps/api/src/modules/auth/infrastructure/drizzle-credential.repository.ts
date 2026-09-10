import { eq } from 'drizzle-orm';

import { schema, type Executor } from '@yume/db';

import { UserId, type User } from '../../users/domain/user.types.js';
import type { CredentialRepository } from '../domain/auth.repository.js';
import type { Credential } from '../domain/credential.types.js';

export class DrizzleCredentialRepository implements CredentialRepository {
  constructor(private readonly db: Executor) {}

  withExecutor(executor: Executor): DrizzleCredentialRepository {
    return new DrizzleCredentialRepository(executor);
  }

  async findForUser(userId: User['id']): Promise<Credential | null> {
    const rows = await this.db
      .select()
      .from(schema.userCredentials)
      .where(eq(schema.userCredentials.userId, userId))
      .limit(1);

    const row = rows[0];

    if (row === undefined) {
      return null;
    }

    return {
      userId: UserId.of(row.userId),
      passwordHash: row.passwordHash,
      algorithm: row.passwordAlgorithm,
      passwordChangedAt: row.passwordChangedAt,
      failedAttempts: row.failedAttempts,
      lockedUntil: row.lockedUntil,
    };
  }

  async insert(credential: Credential): Promise<void> {
    await this.db.insert(schema.userCredentials).values({
      userId: credential.userId,
      passwordHash: credential.passwordHash,
      passwordAlgorithm: credential.algorithm,
      passwordChangedAt: credential.passwordChangedAt,
      failedAttempts: credential.failedAttempts,
      lockedUntil: credential.lockedUntil,
    });
  }

  async update(credential: Credential): Promise<void> {
    await this.db
      .update(schema.userCredentials)
      .set({
        passwordHash: credential.passwordHash,
        passwordAlgorithm: credential.algorithm,
        passwordChangedAt: credential.passwordChangedAt,
        failedAttempts: credential.failedAttempts,
        lockedUntil: credential.lockedUntil,
        updatedAt: new Date(),
      })
      .where(eq(schema.userCredentials.userId, credential.userId));
  }
}
