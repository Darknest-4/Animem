import { DomainRuleError } from '@yume/core';

export interface PasswordPolicyOptions {
  readonly minLength: number;
  readonly maxLength: number;
  readonly blocklist: readonly string[];
}

/**
 * Length-first, blocklist-based rules, following NIST SP 800-63B.
 *
 * Composition rules ("one uppercase, one symbol") are deliberately absent: they
 * push people toward predictable substitutions like Password1! while ruling out
 * genuinely strong passphrases.
 */
export const DEFAULT_BLOCKLIST: readonly string[] = Object.freeze([
  'password',
  'passw0rd',
  'jelszo',
  'jelszó',
  '123456',
  '12345678',
  '123456789',
  'qwerty',
  'qwertz',
  'abc123',
  'iloveyou',
  'admin',
  'letmein',
  'welcome',
  'animem',
  'yume',
  'anime',
]);

export type WeakPasswordReason =
  | 'too_short'
  | 'too_long'
  | 'blocklisted'
  | 'personal_data'
  | 'predictable';

export class WeakPasswordError extends DomainRuleError {
  constructor(message: string, readonly reason: WeakPasswordReason) {
    super(message, 'auth.weak_password', { reason });
  }
}

export class PasswordPolicy {
  private readonly options: PasswordPolicyOptions;

  constructor(options: Partial<PasswordPolicyOptions> = {}) {
    this.options = {
      minLength: options.minLength ?? 12,
      // An unbounded input to Argon2id is a cheap denial of service.
      maxLength: options.maxLength ?? 4096,
      blocklist: options.blocklist ?? DEFAULT_BLOCKLIST,
    };
  }

  /** @throws WeakPasswordError */
  assertAcceptable(password: string, personalData: readonly string[] = []): void {
    const length = [...password].length;

    if (length < this.options.minLength) {
      throw new WeakPasswordError(
        `Password must be at least ${String(this.options.minLength)} characters long.`,
        'too_short',
      );
    }

    if (length > this.options.maxLength) {
      throw new WeakPasswordError(
        `Password must not exceed ${String(this.options.maxLength)} characters.`,
        'too_long',
      );
    }

    const normalised = password.toLowerCase();

    for (const blocked of this.options.blocklist) {
      if (normalised.includes(blocked)) {
        throw new WeakPasswordError(
          'That password is too common. Choose something less predictable.',
          'blocklisted',
        );
      }
    }

    for (const datum of personalData) {
      const candidate = datum.trim().toLowerCase();

      if (candidate.length >= 4 && normalised.includes(candidate)) {
        throw new WeakPasswordError(
          'Password must not contain your username or email address.',
          'personal_data',
        );
      }
    }

    if (isSingleRepeatedCharacter(normalised) || isSequential(normalised)) {
      throw new WeakPasswordError('That password is too predictable.', 'predictable');
    }
  }
}

function isSingleRepeatedCharacter(password: string): boolean {
  return new Set([...password]).size <= 2;
}

function isSequential(password: string): boolean {
  const characters = [...password];
  let ascending = 0;
  let descending = 0;

  for (let index = 1; index < characters.length; index += 1) {
    const previous = characters[index - 1]?.codePointAt(0) ?? 0;
    const current = characters[index]?.codePointAt(0) ?? 0;
    const delta = current - previous;

    if (delta === 1) ascending += 1;
    if (delta === -1) descending += 1;
  }

  const threshold = Math.max(4, Math.floor(characters.length * 0.8));

  return ascending >= threshold || descending >= threshold;
}
