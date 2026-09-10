import { z } from 'zod';

import { isoDateSchema, isoDateTimeSchema, uuidSchema } from '../common/primitives.js';

export const releaseKindSchema = z.enum(['sub', 'dub', 'raw']);

export const episodeReleaseSchema = z.object({
  id: uuidSchema,
  uploader_id: uuidSchema,
  uploader_name: z.string().nullable(),
  language: z.string(),
  kind: releaseKindSchema,
  host: z.string(),
  url: z.string(),
});

export type EpisodeRelease = z.infer<typeof episodeReleaseSchema>;

export const episodeSchema = z.object({
  id: uuidSchema,
  anime_id: uuidSchema,
  number: z.number().int().min(1),
  title: z.string().nullable(),
  title_japanese: z.string().nullable(),
  synopsis: z.string().nullable(),
  aired_on: isoDateSchema.nullable(),
  duration_seconds: z.number().int().nullable(),
  is_published: z.boolean(),
  releases: z.array(episodeReleaseSchema),
  created_at: isoDateTimeSchema,
});

export type Episode = z.infer<typeof episodeSchema>;

export const episodeListResponseSchema = z.object({ episodes: z.array(episodeSchema) });
export const episodeResponseSchema = z.object({ episode: episodeSchema });

export const createEpisodeRequestSchema = z.object({
  number: z.number().int().min(1).max(32_000),
  title: z.string().trim().min(1).max(400).optional(),
});

export const addReleaseRequestSchema = z.object({
  uploader_id: uuidSchema,
  url: z.string().url().max(1024),
  kind: releaseKindSchema.default('sub'),
  language: z
    .string()
    .regex(/^[a-z]{2}(?:-[a-z]{2})?$/iu, 'Must be an ISO 639-1 code, optionally with a region.')
    .default('hu'),
});

/** Published so an upload form can validate before submitting, not after a 422. */
export const allowedHostsResponseSchema = z.object({ allowed_hosts: z.array(z.string()) });
