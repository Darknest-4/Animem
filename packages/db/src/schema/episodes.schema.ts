import { relations, sql } from 'drizzle-orm';
import {
  bigint,
  boolean,
  date,
  index,
  integer,
  pgTable,
  smallint,
  text,
  uniqueIndex,
  uuid,
  varchar,
} from 'drizzle-orm/pg-core';

import { createdAt, primaryId, updatedAt } from './columns.js';
import { releaseKindEnum } from './enums.js';
import { anime } from './catalogue.schema.js';
import { uploaders } from './uploaders.schema.js';
import { users } from './users.schema.js';

export const episodes = pgTable(
  'episodes',
  {
    id: primaryId(),
    animeId: uuid('anime_id')
      .notNull()
      .references(() => anime.id, { onDelete: 'cascade' }),
    number: smallint('number').notNull(),
    title: varchar('title', { length: 400 }),
    titleJapanese: varchar('title_japanese', { length: 400 }),
    synopsis: text('synopsis'),
    airedOn: date('aired_on', { mode: 'date' }),
    durationSeconds: integer('duration_seconds'),
    isPublished: boolean('is_published').notNull().default(false),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
    createdBy: uuid('created_by').references(() => users.id, { onDelete: 'set null' }),
  },
  (table) => [
    uniqueIndex('episodes_anime_number_key').on(table.animeId, table.number),
    index('episodes_published_idx')
      .on(table.animeId, table.number)
      .where(sql`${table.isPublished}`),
  ],
);

/**
 * One group's upload of one episode.
 *
 * The host is stored alongside the URL because it is validated against an
 * embeddable allowlist on the way in. The legacy site embedded whatever string
 * sat in its `links` table, which made a video page only as safe as its least
 * careful uploader.
 */
export const episodeReleases = pgTable(
  'episode_releases',
  {
    id: primaryId(),
    episodeId: uuid('episode_id')
      .notNull()
      .references(() => episodes.id, { onDelete: 'cascade' }),
    uploaderId: uuid('uploader_id')
      .notNull()
      .references(() => uploaders.id, { onDelete: 'cascade' }),
    language: varchar('language', { length: 8 }).notNull().default('hu'),
    kind: releaseKindEnum('kind').notNull().default('sub'),
    host: varchar('host', { length: 64 }).notNull(),
    url: varchar('url', { length: 1024 }).notNull(),
    createdAt: createdAt(),
    createdBy: uuid('created_by').references(() => users.id, { onDelete: 'set null' }),
  },
  (table) => [
    // One release per group, language and kind: a re-upload replaces rather
    // than duplicating.
    uniqueIndex('episode_releases_unique').on(
      table.episodeId,
      table.uploaderId,
      table.language,
      table.kind,
    ),
    index('episode_releases_uploader_idx').on(table.uploaderId, table.createdAt.desc()),
  ],
);

/** Split from `episodes` so a hot counter update never contends with a read. */
export const episodeViewCounts = pgTable('episode_view_counts', {
  episodeId: uuid('episode_id')
    .primaryKey()
    .references(() => episodes.id, { onDelete: 'cascade' }),
  views: bigint('views', { mode: 'number' }).notNull().default(0),
  updatedAt: updatedAt(),
});

export const episodesRelations = relations(episodes, ({ many, one }) => ({
  anime: one(anime, { fields: [episodes.animeId], references: [anime.id] }),
  releases: many(episodeReleases),
  viewCount: one(episodeViewCounts, {
    fields: [episodes.id],
    references: [episodeViewCounts.episodeId],
  }),
}));

export const episodeReleasesRelations = relations(episodeReleases, ({ one }) => ({
  episode: one(episodes, { fields: [episodeReleases.episodeId], references: [episodes.id] }),
  uploader: one(uploaders, { fields: [episodeReleases.uploaderId], references: [uploaders.id] }),
}));
