import { z } from 'zod';

/**
 * Field-level building blocks.
 *
 * Defined once and reused by every contract, so "what counts as a username" has
 * exactly one answer that the API validates against and the web form displays.
 */

export const uuidSchema = z.string().uuid();

export const slugSchema = z
  .string()
  .min(1)
  .max(200)
  .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/u, 'Must be a lower-case, dash-separated slug.');

export const usernameSchema = z
  .string()
  .min(3, 'Must be at least 3 characters.')
  .max(32, 'Must not exceed 32 characters.')
  .regex(
    /^[a-zA-Z0-9](?:[a-zA-Z0-9_.-]{1,30})[a-zA-Z0-9]$/u,
    'Letters, digits, dot, dash and underscore only, starting and ending with a letter or digit.',
  );

export const emailSchema = z.string().email('Must be a valid email address.').max(254);

/**
 * A password field.
 *
 * Only bounded here. Strength is a domain rule with its own error code, and
 * duplicating it in the schema would mean two places to change and two different
 * messages for the same refusal.
 */
export const passwordSchema = z.string().min(1).max(4096);

export const isoDateSchema = z.string().regex(/^\d{4}-\d{2}-\d{2}$/u, 'Must be YYYY-MM-DD.');

export const isoDateTimeSchema = z.string().datetime({ offset: true });

/** A permission slug: `resource.action`, `resource.*` or `*`. */
export const permissionSlugSchema = z
  .string()
  .regex(/^(?:\*|[a-z][a-z0-9_]*\.(?:\*|[a-z][a-z0-9_]*))$/u);
