/**
 * How a route decides who may reach it.
 *
 * There is no default and no fourth option. Because `access` is a required
 * field on a route definition, forgetting it is a *compile* error — the PHP
 * version of this codebase could only catch it at boot, and the site it replaced
 * could not catch it at all: a commented-out .htaccess block left the whole
 * admin area open and nothing noticed.
 */
export type Access =
  /** No session, no permission. Health checks, sign-in, mailed-token endpoints. */
  | { readonly kind: 'public' }
  /** A valid session, nothing more. */
  | { readonly kind: 'authenticated' }
  /**
   * The listed permissions, all of them.
   *
   * Deliberately does *not* imply a session. The `guest` role holds a real
   * permission set, so an anonymous visitor reaches `anime.view` and is refused
   * `anime.edit`. Requiring a session here would force every public read to opt
   * out of permission checking altogether.
   */
  | { readonly kind: 'permissions'; readonly permissions: readonly [string, ...string[]] };

export const access = Object.freeze({
  public: (): Access => ({ kind: 'public' }),
  authenticated: (): Access => ({ kind: 'authenticated' }),
  permissions: (...permissions: readonly [string, ...string[]]): Access => {
    for (const permission of permissions) {
      if (permission === '*' || permission.endsWith('.*')) {
        // A wildcard requirement is satisfied by any role holding any wildcard,
        // which is never what the author meant.
        throw new Error(`A route may not require the wildcard permission "${permission}".`);
      }
    }

    return { kind: 'permissions', permissions };
  },
});

export function requiresAuthentication(value: Access): boolean {
  return value.kind === 'authenticated';
}

export function isPublic(value: Access): boolean {
  return value.kind === 'public';
}

export function requiredPermissions(value: Access): readonly string[] {
  return value.kind === 'permissions' ? value.permissions : [];
}
