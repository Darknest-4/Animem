-- Security: audit trail, bans, rate limiting, network reputation.
--
-- The legacy site had none of this. A compromise there would have been both
-- undetectable and unreconstructable.

CREATE TABLE security_events (
    id          UUID PRIMARY KEY,
    event_type  VARCHAR(64)  NOT NULL,
    severity    VARCHAR(16)  NOT NULL DEFAULT 'info',
    user_id     UUID         NULL REFERENCES users (id) ON DELETE SET NULL,
    ip          INET         NOT NULL,
    user_agent  VARCHAR(512) NOT NULL DEFAULT '',
    method      VARCHAR(10)  NOT NULL DEFAULT '',
    path        VARCHAR(512) NOT NULL DEFAULT '',
    risk_score  SMALLINT     NOT NULL DEFAULT 0,
    metadata    JSONB        NOT NULL DEFAULT '{}'::jsonb,
    occurred_at TIMESTAMPTZ  NOT NULL DEFAULT now(),

    CONSTRAINT security_events_severity_check
        CHECK (severity IN ('info', 'notice', 'warning', 'critical'))
);

-- Serves the risk engine's "recent failed logins from this IP" counter.
CREATE INDEX security_events_ip_type_time_idx ON security_events (ip, event_type, occurred_at DESC);
CREATE INDEX security_events_user_time_idx    ON security_events (user_id, occurred_at DESC);
CREATE INDEX security_events_severity_idx     ON security_events (severity, occurred_at DESC)
    WHERE severity IN ('warning', 'critical');

CREATE TABLE bans (
    id         UUID PRIMARY KEY,
    scope      VARCHAR(16)  NOT NULL,
    ban_type   VARCHAR(16)  NOT NULL,
    -- IP address, subnet key, or user id, depending on scope. NULL for a global ban.
    subject    VARCHAR(128) NULL,
    reason     TEXT         NOT NULL,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now(),
    -- NULL means permanent.
    expires_at TIMESTAMPTZ  NULL,
    lifted_at  TIMESTAMPTZ  NULL,
    created_by UUID         NULL REFERENCES users (id) ON DELETE SET NULL,

    CONSTRAINT bans_scope_check    CHECK (scope IN ('ip', 'subnet', 'user', 'global')),
    CONSTRAINT bans_type_check     CHECK (ban_type IN ('automatic', 'manual', 'read_only')),
    CONSTRAINT bans_subject_check  CHECK (scope = 'global' OR subject IS NOT NULL)
);

-- The lookup on the hot path: scope + subject, only rows that are still in force.
CREATE INDEX bans_active_idx ON bans (scope, subject)
    WHERE lifted_at IS NULL;

CREATE TABLE rate_limit_hits (
    bucket_key  VARCHAR(255) NOT NULL,
    window_start TIMESTAMPTZ NOT NULL,
    hits        INTEGER      NOT NULL DEFAULT 0,
    expires_at  TIMESTAMPTZ  NOT NULL,

    PRIMARY KEY (bucket_key, window_start)
);

CREATE INDEX rate_limit_hits_expiry_idx ON rate_limit_hits (expires_at);

COMMENT ON TABLE rate_limit_hits IS
    'Fixed-window counters. Postgres-backed by default so the stack has no hard Redis dependency; swap in a Redis limiter when the write rate justifies it.';

CREATE TABLE network_reputation (
    network       CIDR         PRIMARY KEY,
    classification VARCHAR(32) NOT NULL,
    asn           INTEGER      NULL,
    organisation  VARCHAR(255) NULL,
    refreshed_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),

    CONSTRAINT network_reputation_class_check
        CHECK (classification IN ('tor_exit', 'vpn', 'datacentre', 'residential'))
);

-- GiST index so `network >>= :ip` (contains) can use an index rather than a scan.
CREATE INDEX network_reputation_contains_idx ON network_reputation USING gist (network inet_ops);
