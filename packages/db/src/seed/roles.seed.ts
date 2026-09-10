export interface RoleSeed {
  readonly slug: string;
  readonly name: string;
  readonly description: string;
  readonly isSystem: boolean;
  readonly permissions: readonly string[];
}

/**
 * Baseline roles.
 *
 * `guest` is the permission set for callers with no session. It holds real
 * permissions rather than being an empty placeholder, which is what lets the
 * catalogue stay publicly readable without any route having to opt out of
 * permission checking altogether.
 */
export const ROLE_SEED: readonly RoleSeed[] = Object.freeze([
  {
    slug: 'guest',
    name: 'Guest',
    description: 'Not signed in. Public read access only.',
    isSystem: true,
    permissions: ['anime.view', 'episode.view', 'uploader.view'],
  },
  {
    slug: 'user',
    name: 'User',
    description: 'Registered member.',
    isSystem: true,
    permissions: ['anime.view', 'episode.view', 'uploader.view'],
  },
  {
    slug: 'uploader',
    name: 'Uploader',
    description: 'May publish and edit catalogue entries.',
    isSystem: false,
    permissions: [
      'anime.view',
      'anime.create',
      'anime.edit',
      'episode.view',
      'episode.create',
      'episode.edit',
      'uploader.view',
    ],
  },
  {
    slug: 'moderator',
    name: 'Moderator',
    description: 'Community moderation and catalogue corrections.',
    isSystem: false,
    permissions: [
      'admin.access',
      'anime.view',
      'anime.create',
      'anime.edit',
      'anime.delete',
      'episode.view',
      'episode.create',
      'episode.edit',
      'episode.delete',
      'uploader.view',
      'uploader.manage',
      'user.view',
      'stats.view',
      'security.view',
    ],
  },
  {
    slug: 'admin',
    name: 'Admin',
    description: 'Full access.',
    isSystem: true,
    // Granted once as a wildcard rather than enumerated, so a new permission is
    // automatically covered instead of silently missing.
    permissions: ['*'],
  },
]);
