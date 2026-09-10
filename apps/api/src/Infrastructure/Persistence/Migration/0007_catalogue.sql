-- The anime catalogue: fansub groups, anime, genres, studios, episodes, releases.
--
-- This is a normalised replacement for the legacy shape, not a copy of it. Three
-- specific problems from the review are fixed here:
--
--  1. Fansub membership was stored as JSON and queried with
--     JSON_EXTRACT(save,'$.fansub') LIKE '%"12"%'. That can never use an index,
--     and '12' matches '120' the moment the format shifts. It becomes the
--     anime_uploaders join table below.
--  2. `links` and `links3`, `wp_posts` and `wp_posts2` were parallel tables with
--     no documented winner. There is one table per concept here.
--  3. Timestamps were unix integers compared as quoted strings. They are
--     TIMESTAMPTZ, and dates that are genuinely date-only are DATE.

CREATE TABLE uploaders (
    id           UUID PRIMARY KEY,
    slug         VARCHAR(64)  NOT NULL,
    name         VARCHAR(160) NOT NULL,
    description  TEXT         NULL,
    website_url  VARCHAR(512) NULL,
    facebook_url VARCHAR(512) NULL,
    video_url    VARCHAR(512) NULL,
    email        CITEXT       NULL,
    is_active    BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at   TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX uploaders_slug_key ON uploaders (slug);
CREATE INDEX uploaders_name_idx ON uploaders (lower(name));

COMMENT ON TABLE uploaders IS 'Fansub groups. Migrated from the legacy `uploaders` table.';

CREATE TABLE anime (
    id              UUID PRIMARY KEY,
    -- MyAnimeList id. Nullable because not every entry has one, unique because
    -- two rows claiming the same one is always a data error.
    mal_id          INTEGER      NULL,
    slug            VARCHAR(200) NOT NULL,
    title           VARCHAR(400) NOT NULL,
    title_english   VARCHAR(400) NULL,
    title_japanese  VARCHAR(400) NULL,
    synopsis        TEXT         NULL,
    media_type      VARCHAR(16)  NOT NULL DEFAULT 'tv',
    status          VARCHAR(16)  NOT NULL DEFAULT 'finished',
    source          VARCHAR(32)  NULL,
    age_rating      VARCHAR(16)  NULL,
    episode_count   SMALLINT     NULL,
    season          VARCHAR(8)   NULL,
    season_year     SMALLINT     NULL,
    aired_from      DATE         NULL,
    aired_to        DATE         NULL,
    score           NUMERIC(4,2) NULL,
    cover_url       VARCHAR(512) NULL,
    -- Unpublished rows are drafts: visible to editors, invisible to the public.
    is_published    BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT now(),
    created_by      UUID         NULL REFERENCES users (id) ON DELETE SET NULL,

    CONSTRAINT anime_media_type_check
        CHECK (media_type IN ('tv', 'movie', 'ova', 'ona', 'special', 'music')),
    CONSTRAINT anime_status_check
        CHECK (status IN ('airing', 'finished', 'upcoming', 'cancelled')),
    CONSTRAINT anime_season_check
        CHECK (season IS NULL OR season IN ('winter', 'spring', 'summer', 'fall')),
    CONSTRAINT anime_score_check
        CHECK (score IS NULL OR (score >= 0 AND score <= 10)),
    CONSTRAINT anime_aired_order_check
        CHECK (aired_to IS NULL OR aired_from IS NULL OR aired_to >= aired_from)
);

CREATE UNIQUE INDEX anime_slug_key   ON anime (slug);
CREATE UNIQUE INDEX anime_mal_id_key ON anime (mal_id) WHERE mal_id IS NOT NULL;
CREATE INDEX anime_published_idx     ON anime (is_published, created_at DESC);
CREATE INDEX anime_season_idx        ON anime (season_year DESC, season) WHERE season_year IS NOT NULL;
CREATE INDEX anime_status_idx        ON anime (status);
-- Trigram index so `title ILIKE '%needle%'` uses an index instead of a scan.
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX anime_title_trgm_idx ON anime USING gin (title gin_trgm_ops);

CREATE TABLE genres (
    id      SERIAL PRIMARY KEY,
    slug    VARCHAR(64)  NOT NULL,
    name_en VARCHAR(128) NOT NULL,
    name_hu VARCHAR(128) NULL
);

CREATE UNIQUE INDEX genres_slug_key ON genres (slug);

CREATE TABLE anime_genres (
    anime_id UUID    NOT NULL REFERENCES anime (id)  ON DELETE CASCADE,
    genre_id INTEGER NOT NULL REFERENCES genres (id) ON DELETE CASCADE,
    PRIMARY KEY (anime_id, genre_id)
);

CREATE INDEX anime_genres_genre_idx ON anime_genres (genre_id);

CREATE TABLE studios (
    id   SERIAL PRIMARY KEY,
    slug VARCHAR(64)  NOT NULL,
    name VARCHAR(160) NOT NULL
);

CREATE UNIQUE INDEX studios_slug_key ON studios (slug);

CREATE TABLE anime_studios (
    anime_id  UUID    NOT NULL REFERENCES anime (id)   ON DELETE CASCADE,
    studio_id INTEGER NOT NULL REFERENCES studios (id) ON DELETE CASCADE,
    PRIMARY KEY (anime_id, studio_id)
);

-- The replacement for JSON_EXTRACT(save,'$.fansub') LIKE '%"12"%'.
CREATE TABLE anime_uploaders (
    anime_id    UUID NOT NULL REFERENCES anime (id)     ON DELETE CASCADE,
    uploader_id UUID NOT NULL REFERENCES uploaders (id) ON DELETE CASCADE,
    claimed_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (anime_id, uploader_id)
);

CREATE INDEX anime_uploaders_uploader_idx ON anime_uploaders (uploader_id);

CREATE TABLE episodes (
    id             UUID PRIMARY KEY,
    anime_id       UUID         NOT NULL REFERENCES anime (id) ON DELETE CASCADE,
    number         SMALLINT     NOT NULL,
    title          VARCHAR(400) NULL,
    title_japanese VARCHAR(400) NULL,
    synopsis       TEXT         NULL,
    aired_on       DATE         NULL,
    duration_sec   INTEGER      NULL,
    is_published   BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    created_by     UUID         NULL REFERENCES users (id) ON DELETE SET NULL,

    CONSTRAINT episodes_number_check CHECK (number > 0)
);

CREATE UNIQUE INDEX episodes_anime_number_key ON episodes (anime_id, number);
CREATE INDEX episodes_published_idx ON episodes (anime_id, number) WHERE is_published;

CREATE TABLE episode_releases (
    id           UUID PRIMARY KEY,
    episode_id   UUID         NOT NULL REFERENCES episodes (id)  ON DELETE CASCADE,
    uploader_id  UUID         NOT NULL REFERENCES uploaders (id) ON DELETE CASCADE,
    language     VARCHAR(8)   NOT NULL DEFAULT 'hu',
    kind         VARCHAR(8)   NOT NULL DEFAULT 'sub',
    host         VARCHAR(64)  NOT NULL,
    url          VARCHAR(1024) NOT NULL,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT now(),
    created_by   UUID         NULL REFERENCES users (id) ON DELETE SET NULL,

    CONSTRAINT episode_releases_kind_check CHECK (kind IN ('sub', 'dub', 'raw'))
);

-- One release per group, language and kind for a given episode; a second upload
-- replaces rather than duplicates.
CREATE UNIQUE INDEX episode_releases_unique
    ON episode_releases (episode_id, uploader_id, language, kind);
CREATE INDEX episode_releases_uploader_idx ON episode_releases (uploader_id, created_at DESC);

CREATE TABLE episode_view_counts (
    episode_id  UUID PRIMARY KEY REFERENCES episodes (id) ON DELETE CASCADE,
    views       BIGINT      NOT NULL DEFAULT 0,
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

COMMENT ON TABLE episode_view_counts IS
    'Separate from episodes so the hot counter update does not contend with catalogue reads.';
