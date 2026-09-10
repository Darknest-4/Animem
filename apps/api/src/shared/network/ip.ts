import { isIP } from 'node:net';

/**
 * Coarse grouping used for rate limiting and subnet bans.
 *
 * Limiting by exact address is defeated by rotating within a /24 or a /64, which
 * costs an attacker nothing and costs the defender the whole control.
 */
export function subnetKey(ip: string): string {
  if (isIP(ip) === 4) {
    const octets = ip.split('.');

    return `${octets[0] ?? '0'}.${octets[1] ?? '0'}.${octets[2] ?? '0'}.0/24`;
  }

  const groups = expandIpv6(ip).split(':').slice(0, 4);

  return `${groups.join(':')}::/64`;
}

export function isValidIp(value: string): boolean {
  return isIP(value) !== 0;
}

/** True for loopback, link-local and RFC 1918 space. */
export function isPrivateIp(ip: string): boolean {
  if (isIP(ip) === 4) {
    const [a = 0, b = 0] = ip.split('.').map(Number);

    return (
      a === 10 ||
      a === 127 ||
      (a === 172 && b >= 16 && b <= 31) ||
      (a === 192 && b === 168) ||
      (a === 169 && b === 254)
    );
  }

  const normalised = ip.toLowerCase();

  return normalised === '::1' || normalised.startsWith('fc') || normalised.startsWith('fd') || normalised.startsWith('fe80');
}

function expandIpv6(ip: string): string {
  const [head = '', tail = ''] = ip.toLowerCase().split('::');
  const headGroups = head === '' ? [] : head.split(':');
  const tailGroups = tail === '' ? [] : tail.split(':');
  const missing = 8 - headGroups.length - tailGroups.length;

  const groups = ip.includes('::')
    ? [...headGroups, ...Array<string>(Math.max(0, missing)).fill('0'), ...tailGroups]
    : ip.toLowerCase().split(':');

  return groups.map((group) => group.padStart(4, '0')).join(':');
}

/** Whether an address falls inside a CIDR block or matches an exact address. */
export function matchesCidr(ip: string, range: string): boolean {
  if (!range.includes('/')) {
    return ip === range;
  }

  const [subnet = '', bitsRaw = '0'] = range.split('/');
  const bits = Number(bitsRaw);

  const ipBytes = toBytes(ip);
  const subnetBytes = toBytes(subnet);

  if (subnetBytes === null) {
    return false;
  }

  // Also rules out a null `ipBytes`, and rules out comparing an IPv4 address
  // against an IPv6 block: four bytes never equals sixteen, so a v4 client can
  // never match a v6 proxy range by accident.
  if (ipBytes?.length !== subnetBytes.length) {
    return false;
  }

  const wholeBytes = Math.floor(bits / 8);
  const remainingBits = bits % 8;

  for (let index = 0; index < wholeBytes; index += 1) {
    if (ipBytes[index] !== subnetBytes[index]) {
      return false;
    }
  }

  if (remainingBits === 0) {
    return true;
  }

  const mask = (0xff << (8 - remainingBits)) & 0xff;

  return ((ipBytes[wholeBytes] ?? 0) & mask) === ((subnetBytes[wholeBytes] ?? 0) & mask);
}

function toBytes(ip: string): number[] | null {
  const version = isIP(ip);

  if (version === 4) {
    const octets = ip.split('.').map(Number);

    return octets.length === 4 && octets.every((octet) => Number.isInteger(octet) && octet >= 0 && octet <= 255)
      ? octets
      : null;
  }

  if (version === 6) {
    const groups = expandIpv6(ip).split(':');
    const bytes: number[] = [];

    for (const group of groups) {
      const value = Number.parseInt(group, 16);
      bytes.push((value >> 8) & 0xff, value & 0xff);
    }

    return bytes.length === 16 ? bytes : null;
  }

  return null;
}
