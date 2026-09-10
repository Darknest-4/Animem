import { z } from 'zod';

import {
  emailSchema,
  isoDateTimeSchema,
  passwordSchema,
  permissionSlugSchema,
  usernameSchema,
  uuidSchema,
} from '../common/primitives.js';

export const userStatusSchema = z.enum([
  'pending_verification',
  'active',
  'suspended',
  'deactivated',
]);

/**
 * The signed-in user, as the API returns it.
 *
 * Carries the caller's effective permissions so the web app can hide what they
 * cannot do — hiding only, never as the check itself, which happens server-side
 * on every request regardless of what the client believes.
 */
export const authenticatedUserSchema = z.object({
  id: uuidSchema,
  username: usernameSchema,
  email: emailSchema,
  status: userStatusSchema,
  email_verified: z.boolean(),
  roles: z.array(z.string()),
  permissions: z.array(permissionSlugSchema),
  created_at: isoDateTimeSchema,
});

export type AuthenticatedUser = z.infer<typeof authenticatedUserSchema>;

export const registerRequestSchema = z.object({
  username: usernameSchema,
  email: emailSchema,
  password: passwordSchema,
  accepted_terms: z.literal(true, {
    errorMap: () => ({ message: 'You must accept the terms of service to register.' }),
  }),
});

export type RegisterRequest = z.infer<typeof registerRequestSchema>;

export const registerResponseSchema = z.object({ user: authenticatedUserSchema });

export const loginRequestSchema = z.object({
  /** Username or email address, as typed. */
  identifier: z.string().min(3).max(254),
  password: passwordSchema,
});

export type LoginRequest = z.infer<typeof loginRequestSchema>;

export const loginResponseSchema = z.object({
  user: authenticatedUserSchema,
  session: z.object({
    id: uuidSchema,
    expires_at: isoDateTimeSchema,
    csrf_token: z.string(),
  }),
  /**
   * Also set as an HttpOnly cookie.
   *
   * Returned in the body as well so non-browser clients can use it as a bearer
   * token without parsing Set-Cookie.
   */
  token: z.string(),
});

export type LoginResponse = z.infer<typeof loginResponseSchema>;

export const meResponseSchema = z.object({
  user: authenticatedUserSchema,
  csrf_token: z.string().nullable(),
});

export const sessionSchema = z.object({
  id: uuidSchema,
  created_ip: z.string(),
  created_user_agent: z.string(),
  created_at: isoDateTimeSchema,
  last_seen_at: isoDateTimeSchema,
  expires_at: isoDateTimeSchema,
  is_current: z.boolean(),
});

export type Session = z.infer<typeof sessionSchema>;

export const sessionListResponseSchema = z.object({ sessions: z.array(sessionSchema) });

export const verifyEmailRequestSchema = z.object({ token: z.string().min(16).max(128) });

export const resendVerificationRequestSchema = z.object({ email: emailSchema });

export const forgotPasswordRequestSchema = z.object({ email: emailSchema });

export const resetPasswordRequestSchema = z.object({
  token: z.string().min(16).max(128),
  password: passwordSchema,
});

export const changePasswordRequestSchema = z.object({
  current_password: passwordSchema,
  new_password: passwordSchema,
});

/** Endpoints that must not reveal whether an address is registered. */
export const acceptedResponseSchema = z.object({ message: z.string() });
