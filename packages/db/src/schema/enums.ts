import { pgEnum } from 'drizzle-orm/pg-core';

/**
 * Every closed set in the schema, declared once.
 *
 * Postgres enums rather than CHECK constraints on text: the type is visible in
 * the catalogue, Drizzle infers a union from it, and adding a member is a
 * migration rather than a string nobody validates.
 */

export const userStatusEnum = pgEnum('user_status', [
  'pending_verification',
  'active',
  'suspended',
  'deactivated',
]);

/**
 * `legacy_sha256` is read-only.
 *
 * It exists so the ten-year-old unsalted hashes can be upgraded during a normal
 * login instead of forcing a password reset on every account. Nothing ever
 * writes it.
 */
export const passwordAlgorithmEnum = pgEnum('password_algorithm', ['argon2id', 'legacy_sha256']);

export const tokenPurposeEnum = pgEnum('token_purpose', ['email_verification', 'password_reset']);

export const banScopeEnum = pgEnum('ban_scope', ['ip', 'subnet', 'user', 'global']);

export const banTypeEnum = pgEnum('ban_type', ['automatic', 'manual', 'read_only']);

export const severityEnum = pgEnum('severity', ['info', 'notice', 'warning', 'critical']);

export const networkClassificationEnum = pgEnum('network_classification', [
  'tor_exit',
  'vpn',
  'datacentre',
  'residential',
]);

export const rolloutStrategyEnum = pgEnum('rollout_strategy', [
  'off',
  'on',
  'percentage',
  'role_list',
  'user_list',
  'ip_list',
]);

export const mediaTypeEnum = pgEnum('media_type', ['tv', 'movie', 'ova', 'ona', 'special', 'music']);

export const airingStatusEnum = pgEnum('airing_status', [
  'airing',
  'finished',
  'upcoming',
  'cancelled',
]);

export const seasonEnum = pgEnum('season', ['winter', 'spring', 'summer', 'fall']);

export const releaseKindEnum = pgEnum('release_kind', ['sub', 'dub', 'raw']);

export const jobStatusEnum = pgEnum('job_status', [
  'pending',
  'reserved',
  'completed',
  'failed',
]);
