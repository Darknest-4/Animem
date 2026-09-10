/**
 * A typed alternative to throwing, for failures that are part of a function's
 * contract rather than exceptional.
 *
 * Used where the caller must handle both outcomes — a credential check, a token
 * redemption. Genuine faults still throw: wrapping everything in a Result turns
 * every call site into a ceremony and buries the exceptional cases.
 */
export type Result<T, E = Error> =
  | { readonly ok: true; readonly value: T }
  | { readonly ok: false; readonly error: E };

export function ok<T>(value: T): Result<T, never> {
  return { ok: true, value };
}

export function err<E>(error: E): Result<never, E> {
  return { ok: false, error };
}

export function isOk<T, E>(result: Result<T, E>): result is { ok: true; value: T } {
  return result.ok;
}

export function isErr<T, E>(result: Result<T, E>): result is { ok: false; error: E } {
  return !result.ok;
}

/** Unwraps, throwing the contained error. For call sites that cannot proceed. */
export function unwrap<T, E>(result: Result<T, E>): T {
  if (result.ok) {
    return result.value;
  }

  throw result.error instanceof Error ? result.error : new Error(String(result.error));
}

export function mapResult<T, U, E>(result: Result<T, E>, fn: (value: T) => U): Result<U, E> {
  return result.ok ? ok(fn(result.value)) : result;
}
