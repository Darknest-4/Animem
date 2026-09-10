-- Core identity: users, their password credentials and their sessions.
--
-- Replaces the legacy `users` table plus the client-editable `userID` cookie.

CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS citext;

CREATE TABLE users (
    id                  UUID PRIMARY KEY,
    username            VARCHAR(32)  NOT NULL,
    -- Case- and separator-folded form. The uniqueness guarantee lives here, not
    -- in a SELECT-then-INSERT check that races under concurrency.
    username_canonical  VARCHAR(32)  NOT NULL,
    email               CITEXT       NOT NULL,
    email_canonical     CITEXT       NOT NULL,
    status              VARCHAR(32)  NOT NULL DEFAULT 'pending_verification',
    email_verified_at   TIMESTAMPTZ  NULL,
    created_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),

    CONSTRAINT users_status_check
        CHECK (status IN ('pending_verification', 'active', 'suspended', 'deactivated'))
);

CREATE UNIQUE INDEX users_username_canonical_key ON users (username_canonical);
CREATE UNIQUE INDEX users_email_canonical_key    ON users (email_canonical);
CREATE INDEX        users_created_at_idx         ON users (created_at DESC);
CREATE INDEX        users_status_idx             ON users (status) WHERE status <> 'active';

COMMENT ON COLUMN users.username_canonical IS 'Lower-cased with . and - folded to _; the real uniqueness key.';

CREATE TABLE user_credentials (
    user_id             UUID PRIMARY KEY REFERENCES users (id) ON DELETE CASCADE,
    password_hash       TEXT         NOT NULL,
    -- 'legacy_sha256' rows are upgraded to 'argon2id' on the owner's next login.
    password_algorithm  VARCHAR(32)  NOT NULL DEFAULT 'argon2id',
    password_changed_at TIMESTAMPTZ  NULL,
    failed_attempts     SMALLINT     NOT NULL DEFAULT 0,
    locked_until        TIMESTAMPTZ  NULL,
    updated_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),

    CONSTRAINT user_credentials_algorithm_check
        CHECK (password_algorithm IN ('argon2id', 'legacy_sha256'))
);

CREATE INDEX user_credentials_locked_idx ON user_credentials (locked_until)
    WHERE locked_until IS NOT NULL;

CREATE TABLE sessions (
    id                  UUID PRIMARY KEY,
    user_id             UUID         NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    -- SHA-256 of the bearer token. The plaintext exists only in the client's cookie,
    -- so a dump of this table yields no usable sessions.
    token_hash          CHAR(64)     NOT NULL,
    created_ip          INET         NOT NULL,
    created_user_agent  VARCHAR(512) NOT NULL DEFAULT '',
    created_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),
    last_seen_at        TIMESTAMPTZ  NOT NULL DEFAULT now(),
    expires_at          TIMESTAMPTZ  NOT NULL,
    revoked_at          TIMESTAMPTZ  NULL,
    revoked_reason      VARCHAR(64)  NULL
);

CREATE UNIQUE INDEX sessions_token_hash_key ON sessions (token_hash);
CREATE INDEX sessions_user_active_idx ON sessions (user_id, last_seen_at DESC)
    WHERE revoked_at IS NULL;
CREATE INDEX sessions_expires_at_idx  ON sessions (expires_at)
    WHERE revoked_at IS NULL;

CREATE TABLE email_verification_tokens (
    id          UUID PRIMARY KEY,
    user_id     UUID        NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    token_hash  CHAR(64)    NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    expires_at  TIMESTAMPTZ NOT NULL,
    consumed_at TIMESTAMPTZ NULL
);

CREATE UNIQUE INDEX email_verification_tokens_hash_key ON email_verification_tokens (token_hash);
CREATE INDEX email_verification_tokens_user_idx ON email_verification_tokens (user_id);

CREATE TABLE password_reset_tokens (
    id          UUID PRIMARY KEY,
    user_id     UUID        NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    token_hash  CHAR(64)    NOT NULL,
    requested_ip INET       NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    expires_at  TIMESTAMPTZ NOT NULL,
    consumed_at TIMESTAMPTZ NULL
);

CREATE UNIQUE INDEX password_reset_tokens_hash_key ON password_reset_tokens (token_hash);
CREATE INDEX password_reset_tokens_user_idx ON password_reset_tokens (user_id);
