import { relations, sql } from 'drizzle-orm';
import {
  boolean,
  date,
  index,
  integer,
  numeric,
  pgTable,
  primaryKey,
  serial,
  smallint,
  text,
  uniqueIndex,
  uuid,
  varchar,
} from 'drizzle-orm/pg-core';

import { createdAt, primaryId, updatedAt } from './columns.js';
import { airingStatusEnum, mediaTypeEnum, seasonEnum } from './enums.js';
import { users } from './users.schema.js';

export const anime = pgTable(
  'anime',
  {
    id: primaryId(),
    /** MyAnimeList id. Nullable, because not every entry has one; unique,
     *  because two rows claiming the same one is always a data error. */
    malId: integer('mal_id'),
    slug: varchar('slug', { length: 200 }).notNull(),
    title: varchar('title', { length: 400 }).notNull(),
    titleEnglish: varchar('title_english', { length: 400 }),
    titleJapanese: varchar('title_japanese', { length: 400 }),
    synopsis: text('synopsis'),
    mediaType: mediaTypeEnum('media_type').notNull().default('tv'),
    status: airingStatusEnum('status').notNull().default('finished'),
    source: varchar('source', { length: 32 }),
    ageRating: varchar('age_rating', { length: 16 }),
    episodeCount: smallint('episode_count'),
    season: seasonEnum('season'),
    seasonYear: smallint('season_year'),
    airedFrom: date('aired_from', { mode: 'date' }),
    airedTo: date('aired_to', { mode: 'date' }),
    score: numeric('score', { precision: 4, scale: 2 }),
    coverUrl: varchar('cover_url', { length: 512 }),
    /**
     * Unpublished rows are drafts: visible to editors, invisible to everyone
     * else. The legacy admin wrote straight to the live table, so a half-filled
     * entry was public the moment it was created.
     */
    isPublished: boolean('is_published').notNull().default(false),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
    createdBy: uuid('created_by').references(() => users.id, { onDelete: 'set null' }),
  },
  (table) => [
    uniqueIndex('anime_slug_key').on(table.slug),
    uniqueIndex('anime_mal_id_key')
      .on(table.malId)
      .where(sql`${table.malId} IS NOT NULL`),
    index('anime_published_idx').on(table.isPublished, table.createdAt.desc()),
    index('anime_season_idx')
      .on(table.seasonYear.desc(), table.season)
      .where(sql`${table.seasonYear} IS NOT NULL`),
    index('anime_status_idx').on(table.status),
    // Trigram index so `title ILIKE '%needle%'` uses an index rather than
    // scanning the catalogue on every search keystroke.
    index('anime_title_trgm_idx').using('gin', sql`${table.title} gin_trgm_ops`),
  ],
);

export const genres = pgTable(
  'genres',
  {
    id: serial('id').primaryKey(),
    slug: varchar('slug', { length: 64 }).notNull(),
    nameEn: varchar('name_en', { length: 128 }).notNull(),
    nameHu: varchar('name_hu', { length: 128 }),
  },
  (table) => [uniqueIndex('genres_slug_key').on(table.slug)],
);

export const studios = pgTable(
  'studios',
  {
    id: serial('id').primaryKey(),
    slug: varchar('slug', { length: 64 }).notNull(),
    name: varchar('name', { length: 160 }).notNull(),
  },
  (table) => [uniqueIndex('studios_slug_key').on(table.slug)],
);

export const animeGenres = pgTable(
  'anime_genres',
  {
    animeId: uuid('anime_id')
      .notNull()
      .references(() => anime.id, { onDelete: 'cascade' }),
    genreId: integer('genre_id')
      .notNull()
      .references(() => genres.id, { onDelete: 'cascade' }),
  },
  (table) => [
    primaryKey({ columns: [table.animeId, table.genreId] }),
    index('anime_genres_genre_idx').on(table.genreId),
  ],
);

export const animeStudios = pgTable(
  'anime_studios',
  {
    animeId: uuid('anime_id')
      .notNull()
      .references(() => anime.id, { onDelete: 'cascade' }),
    studioId: integer('studio_id')
      .notNull()
      .references(() => studios.id, { onDelete: 'cascade' }),
  },
  (table) => [
    primaryKey({ columns: [table.animeId, table.studioId] }),
    index('anime_studios_studio_idx').on(table.studioId),
  ],
);

export const animeRelations = relations(anime, ({ many, one }) => ({
  genres: many(animeGenres),
  studios: many(animeStudios),
  createdByUser: one(users, { fields: [anime.createdBy], references: [users.id] }),
}));

export const genresRelations = relations(genres, ({ many }) => ({ anime: many(animeGenres) }));
export const studiosRelations = relations(studios, ({ many }) => ({ anime: many(animeStudios) }));

export const animeGenresRelations = relations(animeGenres, ({ one }) => ({
  anime: one(anime, { fields: [animeGenres.animeId], references: [anime.id] }),
  genre: one(genres, { fields: [animeGenres.genreId], references: [genres.id] }),
}));

export const animeStudiosRelations = relations(animeStudios, ({ one }) => ({
  anime: one(anime, { fields: [animeStudios.animeId], references: [anime.id] }),
  studio: one(studios, { fields: [animeStudios.studioId], references: [studios.id] }),
}));
