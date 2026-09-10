import { DomainRuleError, ValidationError } from './errors/index.js';

/**
 * Assertions that read as prose and throw the project's own error types.
 *
 * The point is that a violated invariant surfaces as a 422 with a code, not as
 * a TypeError three frames later.
 */
export const Guard = Object.freeze({
  againstEmpty(value: string, field: string): string {
    const trimmed = value.trim();

    if (trimmed === '') {
      throw new ValidationError({ [field]: ['This field is required.'] });
    }

    return trimmed;
  },

  length(value: string, field: string, min: number, max: number): string {
    const length = [...value].length;

    if (length < min || length > max) {
      throw new ValidationError({
        [field]: [`Must be between ${String(min)} and ${String(max)} characters.`],
      });
    }

    return value;
  },

  range(value: number, field: string, min: number, max: number): number {
    if (Number.isNaN(value) || value < min || value > max) {
      throw new ValidationError({
        [field]: [`Must be between ${String(min)} and ${String(max)}.`],
      });
    }

    return value;
  },

  /** Narrows an untrusted string to a member of a const enum-like object. */
  oneOf<const T extends readonly string[]>(value: string, field: string, allowed: T): T[number] {
    if (!allowed.includes(value)) {
      throw new ValidationError({ [field]: [`Must be one of: ${allowed.join(', ')}.`] });
    }

    return value as T[number];
  },

  /** For invariants that should be impossible, not merely invalid input. */
  invariant(condition: boolean, message: string, code = 'domain.invariant_violated'): asserts condition {
    if (!condition) {
      throw new DomainRuleError(message, code);
    }
  },
});

/**
 * Compile-time exhaustiveness check.
 *
 * Placing this in a switch's default arm turns "someone added an enum member and
 * forgot a branch" from a runtime surprise into a type error.
 */
export function assertNever(value: never, context = 'value'): never {
  throw new Error(`Unhandled ${context}: ${JSON.stringify(value)}`);
}
