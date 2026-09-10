import { ConflictError, ServiceUnavailableError } from '@yume/core';

/** PostgreSQL SQLSTATE codes worth distinguishing from a generic failure. */
const UNIQUE_VIOLATION = '23505';
const FOREIGN_KEY_VIOLATION = '23503';
const CHECK_VIOLATION = '23514';
const CONNECTION_FAILURE = new Set(['08000', '08003', '08006', '08001', '08004', '57P01']);

interface PostgresError {
  readonly code?: string;
  readonly constraint_name?: string;
  readonly detail?: string;
}

function asPostgresError(error: unknown): PostgresError | null {
  if (typeof error !== 'object' || error === null) {
    return null;
  }

  const candidate = error as PostgresError;

  return typeof candidate.code === 'string' ? candidate : null;
}

export function isUniqueViolation(error: unknown, constraint?: string): boolean {
  const pgError = asPostgresError(error);

  if (pgError?.code !== UNIQUE_VIOLATION) {
    return false;
  }

  return constraint === undefined || pgError.constraint_name === constraint;
}

export function isForeignKeyViolation(error: unknown): boolean {
  return asPostgresError(error)?.code === FOREIGN_KEY_VIOLATION;
}

export function isCheckViolation(error: unknown): boolean {
  return asPostgresError(error)?.code === CHECK_VIOLATION;
}

export function isConnectionFailure(error: unknown): boolean {
  const code = asPostgresError(error)?.code;

  return code !== undefined && CONNECTION_FAILURE.has(code);
}

/**
 * Turns a constraint violation into the project's own error type.
 *
 * Repositories check up front for a good message, but the index is what holds
 * under concurrency — two simultaneous registrations both pass the SELECT. This
 * is how the race arrives as a 409 instead of a 500.
 */
export function translateUniqueViolation(
  error: unknown,
  mapping: Readonly<Record<string, { message: string; code: string }>>,
): never {
  const pgError = asPostgresError(error);
  const constraint = pgError?.constraint_name;

  if (constraint !== undefined && constraint in mapping) {
    const mapped = mapping[constraint];

    if (mapped !== undefined) {
      throw new ConflictError(mapped.message, mapped.code, { constraint });
    }
  }

  if (isConnectionFailure(error)) {
    throw new ServiceUnavailableError('The database is unavailable.', {}, { cause: error });
  }

  throw error;
}
