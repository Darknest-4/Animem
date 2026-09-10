import { z } from 'zod';

import { emailSchema, isoDateTimeSchema, slugSchema, uuidSchema } from '../common/primitives.js';

export const uploaderSchema = z.object({
  id: uuidSchema,
  slug: slugSchema,
  name: z.string(),
  description: z.string().nullable(),
  website_url: z.string().nullable(),
  facebook_url: z.string().nullable(),
  video_url: z.string().nullable(),
  is_active: z.boolean(),
  created_at: isoDateTimeSchema,
});

export type Uploader = z.infer<typeof uploaderSchema>;

export const uploaderListResponseSchema = z.object({ uploaders: z.array(uploaderSchema) });
export const uploaderResponseSchema = z.object({ uploader: uploaderSchema });

/**
 * Note the absence of `email`.
 *
 * The contact address is stored for moderators and is not part of any public
 * read model, so it cannot leak by someone adding a field to a serialiser.
 */
export const saveUploaderRequestSchema = z
  .object({
    name: z.string().trim().min(1).max(160),
    slug: slugSchema,
    description: z.string().max(10_000).nullable(),
    website_url: z.string().url().max(512).nullable(),
    facebook_url: z.string().url().max(512).nullable(),
    video_url: z.string().url().max(512).nullable(),
    email: emailSchema.nullable(),
    is_active: z.boolean(),
  })
  .partial();

export type SaveUploaderRequest = z.infer<typeof saveUploaderRequestSchema>;
