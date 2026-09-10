import { z } from 'zod';

import { paginated, paginationQuerySchema } from '../common/pagination.contract.js';
import { userStatusSchema } from '../auth/auth.contract.js';
import {
  emailSchema,
  isoDateTimeSchema,
  permissionSlugSchema,
  usernameSchema,
  uuidSchema,
} from '../common/primitives.js';

// --- users -------------------------------------------------------------------

export const adminUserSchema = z.object({
  id: uuidSchema,
  username: usernameSchema,
  email: emailSchema,
  status: userStatusSchema,
  email_verified: z.boolean(),
  roles: z.array(z.string()),
  created_at: isoDateTimeSchema,
});

export type AdminUser = z.infer<typeof adminUserSchema>;

export const adminUserListQuerySchema = paginationQuerySchema;
export const adminUserListResponseSchema = paginated(adminUserSchema);

export const setUserStatusRequestSchema = z.object({ suspended: z.boolean() });

export const setUserStatusResponseSchema = z.object({
  user: adminUserSchema,
  sessions_revoked: z.number().int(),
});

// --- roles -------------------------------------------------------------------

export const roleSchema = z.object({
  slug: z.string(),
  name: z.string(),
  is_system: z.boolean(),
  permissions: z.array(permissionSlugSchema),
});

export type Role = z.infer<typeof roleSchema>;

export const roleListResponseSchema = z.object({ roles: z.array(roleSchema) });

export const grantRoleRequestSchema = z.object({ role: z.string().min(2).max(64) });

export const userRolesResponseSchema = z.object({
  user_id: uuidSchema,
  roles: z.array(z.string()),
});

// --- feature flags -----------------------------------------------------------

export const rolloutStrategySchema = z.enum([
  'off',
  'on',
  'percentage',
  'role',
  'user_list',
  'ip_list',
]);

export const featureFlagSchema = z.object({
  key: z.string(),
  name: z.string(),
  description: z.string().nullable(),
  strategy: rolloutStrategySchema,
  rollout_percentage: z.number().int().min(0).max(100),
  payload: z.record(z.unknown()),
  expires_at: isoDateTimeSchema.nullable(),
  updated_at: isoDateTimeSchema,
});

export type FeatureFlag = z.infer<typeof featureFlagSchema>;

export const featureFlagListResponseSchema = z.object({ features: z.array(featureFlagSchema) });

export const updateFeatureFlagRequestSchema = z
  .object({
    name: z.string().min(1).max(255),
    description: z.string().max(2000).nullable(),
    strategy: rolloutStrategySchema,
    rollout_percentage: z.number().int().min(0).max(100),
    payload: z.record(z.unknown()),
    expires_at: isoDateTimeSchema.nullable(),
  })
  .partial()
  .refine((value) => Object.keys(value).length > 0, { message: 'Send at least one field.' });

export const featureFlagAuditEntrySchema = z.object({
  id: uuidSchema,
  changed_at: isoDateTimeSchema,
  changed_by: z.string().nullable(),
  before: z.record(z.unknown()),
  after: z.record(z.unknown()),
});

export const featureFlagHistoryResponseSchema = z.object({
  flag_key: z.string(),
  history: z.array(featureFlagAuditEntrySchema),
});

/** What an unprivileged caller sees: a flat map of key to on/off. */
export const publicFeaturesResponseSchema = z.object({ features: z.record(z.boolean()) });

// --- security ----------------------------------------------------------------

export const severitySchema = z.enum(['info', 'notice', 'warning', 'critical']);
export const banScopeSchema = z.enum(['ip', 'subnet', 'user', 'global']);
export const banTypeSchema = z.enum(['automatic', 'manual', 'read_only']);

export const securityEventSchema = z.object({
  id: uuidSchema,
  type: z.string(),
  severity: severitySchema,
  user_id: uuidSchema.nullable(),
  ip: z.string(),
  method: z.string(),
  path: z.string(),
  risk_score: z.number().int(),
  metadata: z.record(z.unknown()),
  occurred_at: isoDateTimeSchema,
});

export type SecurityEvent = z.infer<typeof securityEventSchema>;

export const securityEventListQuerySchema = z.object({
  severity: severitySchema.default('warning'),
  limit: z.coerce.number().int().min(1).max(200).default(50),
});

export const securityEventListResponseSchema = z.object({
  severity: severitySchema,
  events: z.array(securityEventSchema),
});

export const banSchema = z.object({
  id: uuidSchema,
  scope: banScopeSchema,
  type: banTypeSchema,
  subject: z.string().nullable(),
  reason: z.string(),
  created_at: isoDateTimeSchema,
  expires_at: isoDateTimeSchema.nullable(),
  permanent: z.boolean(),
  created_by: uuidSchema.nullable(),
});

export type Ban = z.infer<typeof banSchema>;

export const banListResponseSchema = z.object({ bans: z.array(banSchema) });
export const banResponseSchema = z.object({ ban: banSchema });

export const createBanRequestSchema = z.object({
  // 'global' is absent on purpose: a global ban locks out the admin issuing it,
  // so it is a console operation on the host, where it can also be lifted.
  scope: z.enum(['ip', 'subnet', 'user']),
  type: banTypeSchema.default('manual'),
  subject: z.string().min(1).max(128),
  reason: z.string().trim().min(1).max(2000),
  expires_in_seconds: z.number().int().min(60).max(31_536_000).optional(),
});

// --- stats -------------------------------------------------------------------

export const statsResponseSchema = z.object({
  stats: z.object({
    users: z.object({
      total: z.number().int(),
      active: z.number().int(),
      new_this_week: z.number().int(),
    }),
    catalogue: z.object({
      anime_published: z.number().int(),
      anime_drafts: z.number().int(),
      episodes: z.number().int(),
      releases: z.number().int(),
      uploaders: z.number().int(),
    }),
    sessions: z.object({ active: z.number().int() }),
    security: z.object({
      events_last_24h: z.number().int(),
      critical_last_24h: z.number().int(),
      active_bans: z.number().int(),
    }),
  }),
  generated_at: isoDateTimeSchema,
  cache_ttl_seconds: z.number().int(),
});
