-- Role based access control.
--
-- The legacy perm__user / perm__access / perm__site / perm__method tables had the
-- right shape but were keyed off a forgeable cookie and enforced only inside views.
-- The data model survives; the enforcement moves to the routing layer.

CREATE TABLE permissions (
    id          SERIAL PRIMARY KEY,
    slug        VARCHAR(100) NOT NULL,
    name        VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    category    VARCHAR(64)  NOT NULL DEFAULT 'general',
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),

    -- Enforces the `resource.action` / `resource.*` / `*` shape at the storage layer,
    -- so a typo in a seed file cannot silently create an unreachable permission.
    CONSTRAINT permissions_slug_shape
        CHECK (slug ~ '^(\*|[a-z][a-z0-9_]*\.(\*|[a-z][a-z0-9_]*))$')
);

CREATE UNIQUE INDEX permissions_slug_key ON permissions (slug);

CREATE TABLE roles (
    id          SERIAL PRIMARY KEY,
    slug        VARCHAR(64)  NOT NULL,
    name        VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    -- System roles are referenced by code (guest, user, admin) and cannot be deleted.
    is_system   BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX roles_slug_key ON roles (slug);

CREATE TABLE role_permissions (
    role_id       INTEGER NOT NULL REFERENCES roles (id)       ON DELETE CASCADE,
    permission_id INTEGER NOT NULL REFERENCES permissions (id) ON DELETE CASCADE,
    granted_at    TIMESTAMPTZ NOT NULL DEFAULT now(),

    PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE user_roles (
    user_id    UUID    NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    role_id    INTEGER NOT NULL REFERENCES roles (id) ON DELETE CASCADE,
    granted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    granted_by UUID    NULL REFERENCES users (id) ON DELETE SET NULL,

    PRIMARY KEY (user_id, role_id)
);

CREATE INDEX user_roles_role_idx ON user_roles (role_id);
