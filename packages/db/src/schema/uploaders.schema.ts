import { relations } from 'drizzle-orm';
import { boolean, index, pgTable, primaryKey, text, uniqueIndex, uuid, varchar } from 'drizzle-orm/pg-core';

import { createdAt, primaryId, updatedAt } from './columns.js';
import { anime } from './catalogue.schema.js';

/** Fansub groups. */
export const uploaders = pgTable(
  'uploaders',
  {
    id: primaryId(),
    slug: varchar('slug', { length: 64 }).notNull(),
    name: varchar('name', { length: 160 }).notNull(),
    description: text('description'),
    websiteUrl: varchar('website_url', { length: 512 }),
    facebookUrl: varchar('facebook_url', { length: 512 }),
    videoUrl: varchar('video_url', { length: 512 }),
    email: varchar('email', { length: 254 }),
    /** Groups that stop translating are deactivated, never deleted: their
     *  releases stay attributed. */
    isActive: boolean('is_active').notNull().default(true),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (table) => [uniqueIndex('uploaders_slug_key').on(table.slug), index('uploaders_name_idx').on(table.name)],
);

/**
 * Which groups work on which title.
 *
 * The replacement for the legacy `JSON_EXTRACT(save,'$.fansub') LIKE '%"12"%'`
 * lookup, which could never use an index and matched '120' as readily as '12'.
 */
export const animeUploaders = pgTable(
  'anime_uploaders',
  {
    animeId: uuid('anime_id')
      .notNull()
      .references(() => anime.id, { onDelete: 'cascade' }),
    uploaderId: uuid('uploader_id')
      .notNull()
      .references(() => uploaders.id, { onDelete: 'cascade' }),
    claimedAt: createdAt(),
  },
  (table) => [
    primaryKey({ columns: [table.animeId, table.uploaderId] }),
    index('anime_uploaders_uploader_idx').on(table.uploaderId),
  ],
);

export const uploadersRelations = relations(uploaders, ({ many }) => ({
  animeUploaders: many(animeUploaders),
}));

export const animeUploadersRelations = relations(animeUploaders, ({ one }) => ({
  anime: one(anime, { fields: [animeUploaders.animeId], references: [anime.id] }),
  uploader: one(uploaders, { fields: [animeUploaders.uploaderId], references: [uploaders.id] }),
}));
