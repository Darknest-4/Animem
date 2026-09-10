import { z } from 'zod';

import { paginated, paginationQuerySchema } from '../common/pagination.contract.js';
import { isoDateSchema, isoDateTimeSchema, slugSchema, uuidSchema } from '../common/primitives.js';

export const mediaTypeSchema = z.enum(['tv', 'movie', 'ova', 'ona', 'special', 'music']);
export const airingStatusSchema = z.enum(['airing', 'finished', 'upcoming', 'cancelled']);
export const seasonSchema = z.enum(['winter', 'spring', 'summer', 'fall']);
export const animeSortSchema = z.enum(['recent', 'title', 'score', 'season']);

export type MediaType = z.infer<typeof mediaTypeSchema>;
export type AiringStatus = z.infer<typeof airingStatusSchema>;
export type Season = z.infer<typeof seasonSchema>;

/** The trimmed shape a list returns: enough to render a card, no more. */
export const animeSummarySchema = z.object({
  id: uuidSchema,
  slug: slugSchema,
  title: z.string(),
  media_type: mediaTypeSchema,
  status: airingStatusSchema,
  season: seasonSchema.nullable(),
  season_year: z.number().int().nullable(),
  score: z.number().nullable(),
  cover_url: z.string().nullable(),
  genres: z.array(z.string()),
  is_published: z.boolean(),
});

export type AnimeSummary = z.infer<typeof animeSummarySchema>;

export const animeDetailSchema = animeSummarySchema.extend({
  title_english: z.string().nullable(),
  title_japanese: z.string().nullable(),
  synopsis: z.string().nullable(),
  mal_id: z.number().int().nullable(),
  source: z.string().nullable(),
  age_rating: z.string().nullable(),
  episode_count: z.number().int().nullable(),
  aired_from: isoDateSchema.nullable(),
  aired_to: isoDateSchema.nullable(),
  studios: z.array(z.string()),
  uploaders: z.array(z.object({ id: uuidSchema, slug: slugSchema, name: z.string() })),
  created_at: isoDateTimeSchema,
  updated_at: isoDateTimeSchema,
});

export type AnimeDetail = z.infer<typeof animeDetailSchema>;

export const animeListQuerySchema = paginationQuerySchema.extend({
  q: z.string().trim().min(1).max(200).optional(),
  genre: slugSchema.optional(),
  type: mediaTypeSchema.optional(),
  status: airingStatusSchema.optional(),
  season: seasonSchema.optional(),
  year: z.coerce.number().int().min(1900).max(2200).optional(),
  sort: animeSortSchema.default('recent'),
});

export type AnimeListQuery = z.infer<typeof animeListQuerySchema>;

export const animeListResponseSchema = paginated(animeSummarySchema);
export const animeDetailResponseSchema = z.object({ anime: animeDetailSchema });

export const createAnimeRequestSchema = z.object({
  title: z.string().trim().min(1).max(400),
  media_type: mediaTypeSchema,
  mal_id: z.number().int().min(1).max(2_000_000).optional(),
});

/**
 * A partial update.
 *
 * Every field is optional and nullable where the column is, so omitting a field
 * leaves it alone while sending null clears it — a distinction a flat "all
 * fields optional" shape cannot express.
 */
export const updateAnimeRequestSchema = z
  .object({
    title: z.string().trim().min(1).max(400),
    slug: slugSchema,
    title_english: z.string().max(400).nullable(),
    title_japanese: z.string().max(400).nullable(),
    synopsis: z.string().max(20_000).nullable(),
    media_type: mediaTypeSchema,
    status: airingStatusSchema,
    mal_id: z.number().int().min(1).max(2_000_000).nullable(),
    source: z.string().max(32).nullable(),
    age_rating: z.string().max(16).nullable(),
    episode_count: z.number().int().min(0).max(32_000).nullable(),
    aired_from: isoDateSchema.nullable(),
    aired_to: isoDateSchema.nullable(),
    score: z.number().min(0).max(10).nullable(),
    cover_url: z.string().url().max(512).nullable(),
    genres: z.array(slugSchema).max(30),
    studios: z.array(slugSchema).max(20),
    uploaders: z.array(uuidSchema).max(50),
  })
  .partial()
  .refine((value) => Object.keys(value).length > 0, {
    message: 'Send at least one field to update.',
  });

export type UpdateAnimeRequest = z.infer<typeof updateAnimeRequestSchema>;

export const publishRequestSchema = z.object({ published: z.boolean() });
