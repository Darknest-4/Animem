import { createHmac, randomBytes, timingSafeEqual } from 'node:crypto';

/**
 * Double-submit CSRF tokens, bound to a session with an HMAC.
 *
 * Needed because the session cookie is SameSite=Lax rather than Strict: Lax
 * still permits top-level cross-site POSTs. Binding to the session id is what
 * stops a token minted for one session being replayed into another.
 */
export class CsrfTokenManager {
  constructor(private readonly secret: string) {
    if (secret.length < 32) {
      throw new Error('CSRF secret must be at least 32 characters.');
    }
  }

  generate(sessionId: string): string {
    const nonce = randomBytes(16).toString('hex');

    return `${nonce}.${this.sign(sessionId, nonce)}`;
  }

  isValid(sessionId: string, token: string | undefined | null): boolean {
    if (typeof token !== 'string' || !token.includes('.')) {
      return false;
    }

    const separator = token.indexOf('.');
    const nonce = token.slice(0, separator);
    const signature = token.slice(separator + 1);
    const expected = this.sign(sessionId, nonce);

    const a = Buffer.from(signature, 'utf8');
    const b = Buffer.from(expected, 'utf8');

    return a.length === b.length && timingSafeEqual(a, b);
  }

  private sign(sessionId: string, nonce: string): string {
    return createHmac('sha256', this.secret).update(`${sessionId}|${nonce}`).digest('hex');
  }
}
