import { isValidIp, matchesCidr } from './ip.js';

/**
 * Resolves the real client address behind the edge.
 *
 * The site this replaces read HTTP_CLIENT_IP and X-Forwarded-For straight from
 * the request and wrote the result into SQL, so any visitor could choose their
 * own IP address and evade every per-address control. Here a forwarding header
 * is honoured only when the immediate peer is a configured proxy, and the chain
 * is walked from the right, dropping hops we trust — the first untrusted entry
 * is the client. Taking the leftmost entry instead would take whatever the
 * client injected.
 */
export class TrustedProxyResolver {
  constructor(private readonly trustedProxies: readonly string[]) {}

  resolve(remoteAddress: string | undefined, headers: Readonly<Record<string, string | undefined>>): string {
    const peer = typeof remoteAddress === 'string' && isValidIp(remoteAddress) ? remoteAddress : null;

    if (peer === null) {
      // No usable peer address (a unix socket, a malformed SAPI): fall back to a
      // documented sentinel rather than trusting a header.
      return '127.0.0.1';
    }

    if (!this.isTrusted(peer)) {
      return peer;
    }

    const forwardedFor = headers['x-forwarded-for'];

    if (forwardedFor === undefined || forwardedFor.trim() === '') {
      const realIp = headers['x-real-ip'];

      return realIp !== undefined && isValidIp(realIp.trim()) ? realIp.trim() : peer;
    }

    const chain = forwardedFor
      .split(',')
      .map((entry) => entry.trim())
      .filter((entry) => entry !== '');

    for (let index = chain.length - 1; index >= 0; index -= 1) {
      const candidate = chain[index];

      if (candidate === undefined || !isValidIp(candidate)) {
        continue;
      }

      if (!this.isTrusted(candidate)) {
        return candidate;
      }
    }

    return peer;
  }

  isTrusted(ip: string): boolean {
    return this.trustedProxies.some((range) => matchesCidr(ip, range));
  }
}
