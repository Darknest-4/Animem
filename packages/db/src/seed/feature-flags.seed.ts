export interface FeatureFlagSeed {
  readonly flagKey: string;
  readonly name: string;
  readonly description: string;
  readonly strategy: 'off' | 'on' | 'percentage' | 'role_list' | 'user_list' | 'ip_list';
  readonly rolloutPercentage: number;
}

/**
 * Flags that exist from day one.
 *
 * Most start `off`. Shipping an unfinished path dark is the entire point: the
 * codebase this replaces contained four rewrites that were permanently half-live
 * because there was no way to do that.
 */
export const FEATURE_FLAG_SEED: readonly FeatureFlagSeed[] = Object.freeze([
  {
    flagKey: 'registration_open',
    name: 'Open registration',
    description: 'Allows self-service account registration.',
    strategy: 'on',
    rolloutPercentage: 100,
  },
  {
    flagKey: 'email_notifications',
    name: 'Email notifications',
    description: 'Outbound mail. Off until SMTP credentials are provisioned.',
    strategy: 'off',
    rolloutPercentage: 0,
  },
  {
    flagKey: 'jikan_enrichment',
    name: 'Jikan enrichment',
    description: 'Backfills catalogue metadata from the Jikan API.',
    strategy: 'off',
    rolloutPercentage: 0,
  },
  {
    flagKey: 'public_watchlists',
    name: 'Public watchlists',
    description: 'Lets members share a watchlist by link.',
    strategy: 'off',
    rolloutPercentage: 0,
  },
  {
    flagKey: 'graphql_api',
    name: 'GraphQL API',
    description: 'Exposes /graphql alongside REST.',
    strategy: 'off',
    rolloutPercentage: 0,
  },
]);
