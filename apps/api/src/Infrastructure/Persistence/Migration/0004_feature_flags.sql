-- Feature flags: the mechanism that lets an unfinished rewrite ship dark.

CREATE TABLE feature_flags (
    id                 SERIAL PRIMARY KEY,
    flag_key           VARCHAR(100) NOT NULL,
    name               VARCHAR(255) NOT NULL,
    description        TEXT         NULL,
    strategy           VARCHAR(16)  NOT NULL DEFAULT 'off',
    rollout_percentage SMALLINT     NOT NULL DEFAULT 0,
    -- Strategy-specific data: {"roles": [...]}, {"users": [...]}, {"ips": [...]}.
    payload            JSONB        NOT NULL DEFAULT '{}'::jsonb,
    created_at         TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at         TIMESTAMPTZ  NOT NULL DEFAULT now(),
    -- Every flag gets a deadline. A flag with no expiry becomes permanent
    -- configuration, and permanent configuration hidden in a flag table is how
    -- codebases end up with four half-live rewrites.
    expires_at         TIMESTAMPTZ  NULL,

    CONSTRAINT feature_flags_strategy_check
        CHECK (strategy IN ('off', 'on', 'percentage', 'role', 'user_list', 'ip_list')),
    CONSTRAINT feature_flags_rollout_check
        CHECK (rollout_percentage BETWEEN 0 AND 100),
    CONSTRAINT feature_flags_key_shape
        CHECK (flag_key ~ '^[a-z][a-z0-9_]{1,98}[a-z0-9]$')
);

CREATE UNIQUE INDEX feature_flags_key_key ON feature_flags (flag_key);
CREATE INDEX feature_flags_expiring_idx ON feature_flags (expires_at)
    WHERE expires_at IS NOT NULL;

CREATE TABLE feature_flag_audit (
    id          UUID PRIMARY KEY,
    flag_key    VARCHAR(100) NOT NULL,
    changed_by  UUID         NULL REFERENCES users (id) ON DELETE SET NULL,
    before_state JSONB       NOT NULL DEFAULT '{}'::jsonb,
    after_state  JSONB       NOT NULL DEFAULT '{}'::jsonb,
    changed_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX feature_flag_audit_key_idx ON feature_flag_audit (flag_key, changed_at DESC);
