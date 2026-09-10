/**
 * The permission catalogue.
 *
 * `resource.action` throughout. A role may be granted `anime.*` or `*`; a route
 * may never *require* a wildcard, which is checked where routes are declared.
 */
export interface PermissionSeed {
  readonly slug: string;
  readonly name: string;
  readonly category: string;
}

export const PERMISSION_SEED: readonly PermissionSeed[] = Object.freeze([
  { slug: '*', name: 'Full access', category: 'system' },

  { slug: 'admin.access', name: 'Access the admin area', category: 'admin' },

  { slug: 'user.view', name: 'View users', category: 'user' },
  { slug: 'user.manage', name: 'Create, edit and suspend users', category: 'user' },

  { slug: 'role.view', name: 'View roles', category: 'authorization' },
  { slug: 'role.manage', name: 'Assign and revoke roles', category: 'authorization' },

  { slug: 'feature_flag.view', name: 'View feature flags', category: 'platform' },
  { slug: 'feature_flag.manage', name: 'Change feature flags', category: 'platform' },

  { slug: 'security.view', name: 'Read the security audit log', category: 'security' },
  { slug: 'security.manage', name: 'Apply and lift bans', category: 'security' },

  { slug: 'anime.view', name: 'View anime entries', category: 'catalogue' },
  { slug: 'anime.create', name: 'Create anime entries', category: 'catalogue' },
  { slug: 'anime.edit', name: 'Edit anime entries', category: 'catalogue' },
  { slug: 'anime.delete', name: 'Delete anime entries', category: 'catalogue' },

  { slug: 'episode.view', name: 'View episodes', category: 'catalogue' },
  { slug: 'episode.create', name: 'Create episodes', category: 'catalogue' },
  { slug: 'episode.edit', name: 'Edit episodes', category: 'catalogue' },
  { slug: 'episode.delete', name: 'Delete episodes', category: 'catalogue' },

  { slug: 'uploader.view', name: 'View fansub groups', category: 'community' },
  { slug: 'uploader.manage', name: 'Manage fansub groups', category: 'community' },

  { slug: 'stats.view', name: 'View site statistics', category: 'platform' },
]);
