-- Baseline roles, permissions and feature flags.
--
-- Idempotent (ON CONFLICT DO NOTHING) so it is safe to re-run and so the seed can
-- be extended in later migrations without forking behaviour between environments.

INSERT INTO permissions (slug, name, category) VALUES
    ('*',                  'Full access',                'system'),
    ('admin.access',       'Access the admin area',      'admin'),
    ('user.view',          'View users',                 'user'),
    ('user.manage',        'Create, edit, suspend users','user'),
    ('role.view',          'View roles',                 'authorization'),
    ('role.manage',        'Assign and revoke roles',    'authorization'),
    ('feature_flag.view',  'View feature flags',         'platform'),
    ('feature_flag.manage','Change feature flags',       'platform'),
    ('security.view',      'Read the security audit log','security'),
    ('security.manage',    'Apply and lift bans',        'security'),
    ('anime.view',         'View anime entries',         'catalogue'),
    ('anime.create',       'Create anime entries',       'catalogue'),
    ('anime.edit',         'Edit anime entries',         'catalogue'),
    ('anime.delete',       'Delete anime entries',       'catalogue'),
    ('episode.view',       'View episodes',              'catalogue'),
    ('episode.create',     'Create episodes',            'catalogue'),
    ('episode.edit',       'Edit episodes',              'catalogue'),
    ('episode.delete',     'Delete episodes',            'catalogue'),
    ('uploader.view',      'View fansub groups',         'community'),
    ('uploader.manage',    'Manage fansub groups',       'community'),
    ('stats.view',         'View site statistics',       'platform')
ON CONFLICT (slug) DO NOTHING;

INSERT INTO roles (slug, name, description, is_system) VALUES
    ('guest',     'Guest',     'Not signed in. Public read access only.',            TRUE),
    ('user',      'User',      'Registered member.',                                  TRUE),
    ('uploader',  'Uploader',  'May publish and edit their own catalogue entries.',   FALSE),
    ('moderator', 'Moderator', 'Community moderation and catalogue corrections.',     FALSE),
    ('admin',     'Admin',     'Full access.',                                        TRUE)
ON CONFLICT (slug) DO NOTHING;

-- guest: public reads only
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'guest' AND p.slug IN ('anime.view', 'episode.view', 'uploader.view')
ON CONFLICT DO NOTHING;

-- user: everything a guest can do
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'user' AND p.slug IN ('anime.view', 'episode.view', 'uploader.view')
ON CONFLICT DO NOTHING;

-- uploader: create and edit catalogue content
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'uploader' AND p.slug IN (
    'anime.view', 'anime.create', 'anime.edit',
    'episode.view', 'episode.create', 'episode.edit',
    'uploader.view'
)
ON CONFLICT DO NOTHING;

-- moderator: uploader plus deletion, user visibility and stats
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'moderator' AND p.slug IN (
    'admin.access',
    'anime.view', 'anime.create', 'anime.edit', 'anime.delete',
    'episode.view', 'episode.create', 'episode.edit', 'episode.delete',
    'uploader.view', 'uploader.manage',
    'user.view', 'stats.view', 'security.view'
)
ON CONFLICT DO NOTHING;

-- admin: the wildcard, granted once rather than enumerated
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'admin' AND p.slug = '*'
ON CONFLICT DO NOTHING;

-- Feature flags corresponding to the half-finished switches found in the legacy
-- codebase. All start at 'off': shipping them dark is the entire point.
INSERT INTO feature_flags (flag_key, name, description, strategy, rollout_percentage) VALUES
    ('new_router',          'New router',            'Route requests through the Yume kernel instead of legacy html/index.php.', 'off', 0),
    ('new_fansub_page',     'New fansub page',       'Replaces the $newSite["fansub"] branch of the legacy index.',              'off', 0),
    ('new_episode_list',    'New episode list',      'Replaces the $newSite["episodelistnew"] branch.',                          'off', 0),
    ('fansub2_preview',     'Fansub v2 preview',     'Replaces the hardcoded IP check in html/Newindex.php.',                    'off', 0),
    ('jikan_enrichment',    'Jikan enrichment',      'Backfills datasheet metadata from the Jikan API.',                         'off', 0),
    ('email_notifications', 'Email notifications',   'Outbound mail. Off until SMTP credentials are provisioned.',               'off', 0),
    ('registration_open',   'Open registration',     'Allows self-service account registration.',                               'on',  100),
    ('graphql_api',         'GraphQL API',           'Exposes the /graphql endpoint alongside REST.',                            'off', 0)
ON CONFLICT (flag_key) DO NOTHING;
