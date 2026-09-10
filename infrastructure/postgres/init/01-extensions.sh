#!/bin/sh
# Runs once, on the very first boot of an empty data directory.
#
# Only creates extensions. The schema itself is owned by the migrator
# (apps/api/bin/console migrate) so that development, CI and production all
# build it the same way — a fixture dump here would drift from the migrations
# and hide the drift until production.
set -eu

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-SQL
    CREATE EXTENSION IF NOT EXISTS pgcrypto;
    CREATE EXTENSION IF NOT EXISTS citext;
SQL
