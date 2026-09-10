import { createHash, randomBytes, timingSafeEqual } from 'node:crypto';

export interface GeneratedToken {
  /** Returned to the client exactly once. Never persisted. */
  readonly plain: string;
  /** Persisted. Safe to index, and useless to an attacker who steals it. */
  readonly hash: string;
}

/**
 * Opaque bearer tokens for sessions, email verification and password resets.
 *
 * Only the SHA-256 digest is stored, so a database dump yields nothing a
 * attacker can present. A fast digest is correct here and would be wrong for
 * passwords: the input is 32 bytes of CSPRNG output, so there is no dictionary
 * to run and nothing for a slow KDF to buy.
 */
export class TokenGenerator {
  constructor(private readonly byteLength = 32) {}

  generate(): GeneratedToken {
    const plain = randomBytes(this.byteLength).toString('base64url');

    return { plain, hash: TokenGenerator.hash(plain) };
  }

  static hash(plainToken: string): string {
    return createHash('sha256').update(plainToken).digest('hex');
  }

  static matches(plainToken: string, storedHash: string): boolean {
    const expected = Buffer.from(storedHash, 'utf8');
    const actual = Buffer.from(TokenGenerator.hash(plainToken), 'utf8');

    return expected.length === actual.length && timingSafeEqual(expected, actual);
  }
}
