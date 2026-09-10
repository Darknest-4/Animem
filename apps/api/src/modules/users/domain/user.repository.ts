import type { PageRequest } from '@yume/core';

import type { User, UserId } from './user.types.js';

export interface UserRepository {
  findById(id: UserId): Promise<User | null>;
  findByEmail(email: string): Promise<User | null>;
  findByUsername(username: string): Promise<User | null>;
  /** Accepts either form, as typed at the sign-in field. */
  findByIdentifier(identifier: string): Promise<User | null>;

  usernameExists(username: string): Promise<boolean>;
  emailExists(email: string): Promise<boolean>;

  /** Inserts a new user together with its canonical keys. */
  insert(user: User & { usernameCanonical: string; emailCanonical: string }): Promise<void>;
  update(user: User & { emailCanonical?: string }): Promise<void>;

  paginate(request: PageRequest): Promise<{ items: readonly User[]; total: number }>;
  countWithRole(roleSlug: string): Promise<number>;
}
