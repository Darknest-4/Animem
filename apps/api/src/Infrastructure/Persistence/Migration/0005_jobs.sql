-- Background jobs for the worker container.
--
-- Postgres-backed rather than Redis-backed: the job rate here is low, and one
-- fewer stateful service is one fewer thing to operate. SKIP LOCKED gives safe
-- concurrent reservation across workers.

CREATE TABLE jobs (
    id           UUID PRIMARY KEY,
    queue        VARCHAR(64)  NOT NULL DEFAULT 'default',
    name         VARCHAR(128) NOT NULL,
    payload      JSONB        NOT NULL DEFAULT '{}'::jsonb,
    attempts     SMALLINT     NOT NULL DEFAULT 0,
    max_attempts SMALLINT     NOT NULL DEFAULT 5,
    available_at TIMESTAMPTZ  NOT NULL DEFAULT now(),
    reserved_at  TIMESTAMPTZ  NULL,
    completed_at TIMESTAMPTZ  NULL,
    failed_at    TIMESTAMPTZ  NULL,
    last_error   TEXT         NULL,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX jobs_reservable_idx ON jobs (queue, available_at)
    WHERE reserved_at IS NULL AND completed_at IS NULL AND failed_at IS NULL;
CREATE INDEX jobs_failed_idx ON jobs (failed_at DESC) WHERE failed_at IS NOT NULL;
