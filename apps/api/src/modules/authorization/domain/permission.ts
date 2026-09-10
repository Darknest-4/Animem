import { DomainRuleError } from '@yume/core';

const SLUG_PATTERN = /^(?:\*|[a-z][a-z0-9_]*\.(?:\*|[a-z][a-z0-9_]*))$/u;

export function isPermissionSlug(value: string): boolean {
  return SLUG_PATTERN.test(value);
}

export function assertPermissionSlug(value: string): string {
  if (!isPermissionSlug(value)) {
    throw new DomainRuleError(
      `Permission "${value}" must look like "resource.action", "resource.*" or "*".`,
      'permission.invalid_slug',
    );
  }

  return value;
}

function resourceOf(slug: string): string {
  return slug === '*' ? '*' : (slug.split('.', 1)[0] ?? '*');
}

/**
 * Whether a granted slug satisfies a required one.
 *
 * Wildcards are a *granting* concept only: `anime.*` covers every action on
 * anime and `*` covers everything, but a route may never require either — that
 * check lives where routes are declared, because a route asking for `*` would be
 * satisfied by any role holding any wildcard.
 */
export function grants(granted: string, required: string): boolean {
  if (granted === '*' || granted === required) {
    return true;
  }

  return granted.endsWith('.*') && resourceOf(granted) === resourceOf(required);
}

/** The effective, flattened set of permissions held by one actor. */
export class PermissionSet {
  private readonly slugs: readonly string[];

  private constructor(slugs: readonly string[]) {
    this.slugs = slugs;
  }

  static empty(): PermissionSet {
    return new PermissionSet([]);
  }

  static from(slugs: Iterable<string>): PermissionSet {
    const unique = new Set<string>();

    for (const slug of slugs) {
      unique.add(slug.toLowerCase());
    }

    return new PermissionSet([...unique]);
  }

  allows(required: string): boolean {
    return this.slugs.some((granted) => grants(granted, required));
  }

  allowsAll(required: readonly string[]): boolean {
    return required.every((slug) => this.allows(slug));
  }

  toArray(): readonly string[] {
    return this.slugs;
  }

  get size(): number {
    return this.slugs.length;
  }
}
