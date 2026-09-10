import { randomUUID, randomBytes } from 'node:crypto';

const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

export function isUuid(value: string): boolean {
  return UUID_PATTERN.test(value);
}

/**
 * UUIDv7: a 48-bit millisecond timestamp followed by 74 random bits.
 *
 * Chosen over v4 because the leading timestamp keeps B-tree inserts append-only
 * in PostgreSQL, which matters on the high-write tables (sessions, audit events,
 * rate-limit buckets). Sequential integers would do that too, but they leak row
 * counts and invite enumeration.
 */
export function uuidV7(now: number = Date.now()): string {
  const bytes = randomBytes(16);

  // 48-bit big-endian timestamp in the first six bytes.
  bytes.writeUIntBE(now, 0, 6);

  // Version 7 in the high nibble of byte 6.
  bytes[6] = ((bytes[6] ?? 0) & 0x0f) | 0x70;
  // RFC 9562 variant in the two high bits of byte 8.
  bytes[8] = ((bytes[8] ?? 0) & 0x3f) | 0x80;

  const hex = bytes.toString('hex');

  return [
    hex.slice(0, 8),
    hex.slice(8, 12),
    hex.slice(12, 16),
    hex.slice(16, 20),
    hex.slice(20, 32),
  ].join('-');
}

/** UUIDv4, for the rare case where a sortable id would leak timing. */
export function uuidV4(): string {
  return randomUUID();
}
