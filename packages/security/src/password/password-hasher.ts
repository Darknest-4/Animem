import { hash, verify } from '@node-rs/argon2';
import { createHash, timingSafeEqual } from 'node:crypto';

export interface Argon2Options {
  readonly memoryCostKib: number;
  readonly timeCost: number;
  readonly parallelism: number;
}

export const DEFAULT_ARGON2_OPTIONS: Argon2Options = Object.freeze({
  memoryCostKib: 65_536,
  timeCost: 4,
  parallelism: 2,
});

/**
 * Argon2id hashing, with a read-only path for the legacy scheme.
 *
 * The site this replaces stored unsalted single-round SHA-256 and ran the
 * plaintext through an HTML escaper before hashing, silently mangling any
 * password containing < > & or ". Both mistakes are structurally impossible
 * here: the plaintext is never transformed, and the legacy verifier exists only
 * so an old hash can be upgraded during a normal login instead of forcing a
 * reset on every account.
 */
/**
 * Guards the one assumption this class makes about its dependency.
 *
 * A silent switch to Argon2i or Argon2d would weaken every new hash without any
 * other symptom, so it is checked on the spot instead of trusted.
 */
function assertArgon2id(digest: string): void {
  if (!digest.startsWith('$argon2id$')) {
    throw new Error(
      `Expected an Argon2id hash but the driver produced "${digest.slice(0, 12)}". ` +
        'Pin @node-rs/argon2 and pass the algorithm explicitly.',
    );
  }
}

export class PasswordHasher {
  private readonly options: Argon2Options;

  constructor(options: Partial<Argon2Options> = {}) {
    this.options = { ...DEFAULT_ARGON2_OPTIONS, ...options };
  }

  async hash(plainPassword: string): Promise<string> {
    // The algorithm is left at the library default, which is Argon2id — the
    // exported Algorithm enum is an ambient const enum and cannot be imported
    // under verbatimModuleSyntax. The choice is asserted rather than assumed:
    // assertArgon2id() below fails loudly if a version ever changes it.
    const digest = await hash(plainPassword, {
      memoryCost: this.options.memoryCostKib,
      timeCost: this.options.timeCost,
      parallelism: this.options.parallelism,
    });

    assertArgon2id(digest);

    return digest;
  }

  async verify(plainPassword: string, storedHash: string): Promise<boolean> {
    try {
      return await verify(storedHash, plainPassword);
    } catch {
      // A malformed hash is a failed verification, not a crash.
      return false;
    }
  }

  /**
   * Whether a hash was produced with weaker parameters than the current policy.
   *
   * Parsed from the encoded string rather than re-hashing, so the check costs
   * nothing on the login path.
   */
  needsRehash(storedHash: string): boolean {
    const match = /^\$argon2id\$v=19\$m=(\d+),t=(\d+),p=(\d+)\$/.exec(storedHash);

    if (match === null) {
      // Not Argon2id at all — a legacy hash, which always needs replacing.
      return true;
    }

    const [, memory, time, parallelism] = match;

    return (
      Number(memory) < this.options.memoryCostKib ||
      Number(time) < this.options.timeCost ||
      Number(parallelism) < this.options.parallelism
    );
  }

  /**
   * Verifies a hash produced by the legacy `sha256(password)` call.
   *
   * Used only on the migration path: a match is immediately re-hashed with
   * Argon2id and the legacy marker cleared. Compared in constant time so the
   * dead scheme does not become a timing oracle on its way out.
   */
  verifyLegacySha256(plainPassword: string, legacyHash: string): boolean {
    const expected = Buffer.from(legacyHash.toLowerCase(), 'utf8');
    const actual = Buffer.from(createHash('sha256').update(plainPassword).digest('hex'), 'utf8');

    return expected.length === actual.length && timingSafeEqual(expected, actual);
  }
}
