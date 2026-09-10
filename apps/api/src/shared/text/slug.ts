import { createHash } from 'node:crypto';

export const SLUG_MAX_LENGTH = 200;
const SLUG_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

export function isSlug(value: string): boolean {
  return SLUG_PATTERN.test(value) && value.length <= SLUG_MAX_LENGTH;
}

/**
 * Derives a URL-safe identifier from a title.
 *
 * Unicode is normalised and combining marks stripped, so "Ámokfutó Álom" becomes
 * "amokfuto-alom" rather than being mangled or rejected. A title with no Latin
 * characters at all still needs an identifier, so it falls back to a hash of the
 * original — deterministic, so the same title always produces the same slug.
 */
export function slugify(title: string): string {
  const normalised = title
    .normalize('NFKD')
    .replace(/[̀-ͯ]/gu, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/gu, '-')
    .replace(/^-+|-+$/gu, '');

  if (normalised === '') {
    return `entry-${createHash('sha256').update(title).digest('hex').slice(0, 10)}`;
  }

  return normalised.slice(0, SLUG_MAX_LENGTH).replace(/-+$/u, '');
}

/** Appends a discriminator without exceeding the column limit. */
export function withSlugSuffix(slug: string, suffix: number): string {
  const tail = `-${String(suffix)}`;

  return `${slug.slice(0, SLUG_MAX_LENGTH - tail.length).replace(/-+$/u, '')}${tail}`;
}

/**
 * Case- and separator-insensitive key for a username.
 *
 * Separators are removed rather than normalised, so `kitsune`, `Kit.Sune`,
 * `kit-sune` and `kit_sune` are one name. On a community site the cost of
 * refusing a slightly different spelling is far lower than the cost of letting
 * someone register a name that impersonates an existing member.
 */
export function canonicaliseUsername(username: string): string {
  return username.toLowerCase().replaceAll(/[.\-_]/gu, '');
}

/**
 * Duplicate-detection key for an email address.
 *
 * Gmail ignores dots and everything after a +, so without this
 * kit.sune+anime@gmail.com and kitsune@gmail.com are two accounts on one inbox.
 */
export function canonicaliseEmail(email: string): string {
  const lowered = email.trim().toLowerCase();
  const at = lowered.lastIndexOf('@');

  if (at === -1) {
    return lowered;
  }

  let local = lowered.slice(0, at);
  const domain = lowered.slice(at + 1);

  if (domain === 'gmail.com' || domain === 'googlemail.com') {
    local = local.replaceAll('.', '');
  }

  const plus = local.indexOf('+');

  if (plus !== -1) {
    local = local.slice(0, plus);
  }

  return `${local}@${domain}`;
}
