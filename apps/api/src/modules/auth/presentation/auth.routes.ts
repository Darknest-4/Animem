import { z } from 'zod';

import { UnauthorizedError } from '@yume/core';
import {
  acceptedResponseSchema,
  changePasswordRequestSchema,
  forgotPasswordRequestSchema,
  loginRequestSchema,
  loginResponseSchema,
  meResponseSchema,
  problemSchema,
  registerRequestSchema,
  registerResponseSchema,
  resendVerificationRequestSchema,
  resetPasswordRequestSchema,
  sessionListResponseSchema,
  verifyEmailRequestSchema,
} from '@yume/contracts';

import type { Services } from '../../../container/services.js';
import { access } from '../../../http/access.js';
import { defineRoute, type RouteDefinition } from '../../../http/route.js';
import { toAuthenticatedUser } from '../application/authenticated-user.view.js';
import { SessionId } from '../domain/session.types.js';
import { UserId } from '../../users/domain/user.types.js';

const TAGS = ['auth'] as const;

/**
 * A response the caller must not be able to distinguish from any other.
 *
 * Used by both mail-dispatch endpoints. They answer identically whether or not
 * the address exists, because an anonymous caller who can tell the difference
 * has an account-enumeration oracle.
 */
const ACCEPTED = {
  message: 'If that address is registered, a message is on its way.',
} as const;

/**
 * The authentication endpoints.
 *
 * Every route states its own access rule, rate-limit budget and CSRF stance next
 * to its handler. Reading this file tells you the security posture of the whole
 * module; there is no second place where a URL is mapped to a policy and no way
 * for the two to disagree.
 */
export function authRoutes(services: Services): readonly RouteDefinition[] {
  const { auth, sessionCookie, csrf } = services;

  return [
    defineRoute({
      method: 'POST',
      url: '/auth/register',
      access: access.public(),
      rateLimit: 'auth.register',
      // No session exists yet, so there is no ambient authority to ride.
      csrfExempt: true,
      schema: {
        summary: 'Create an account',
        tags: TAGS,
        body: registerRequestSchema,
        response: { 201: registerResponseSchema, 409: problemSchema, 422: problemSchema },
      },
      async handler(request, reply) {
        const user = await auth.register.execute({
          username: request.body.username,
          email: request.body.email,
          password: request.body.password,
          ip: request.context.clientIp,
          userAgent: request.context.userAgent,
        });

        return reply.status(201).send({ user });
      },
    }),

    defineRoute({
      method: 'POST',
      url: '/auth/login',
      access: access.public(),
      rateLimit: 'auth.login',
      csrfExempt: true,
      schema: {
        summary: 'Sign in',
        tags: TAGS,
        body: loginRequestSchema,
        response: { 200: loginResponseSchema, 401: problemSchema, 423: problemSchema },
      },
      async handler(request, reply) {
        const result = await auth.login.execute({
          identifier: request.body.identifier,
          password: request.body.password,
          ip: request.context.clientIp,
          userAgent: request.context.userAgent,
        });

        // Cookie for browsers, body for everyone else. Both carry the same
        // token; which one a client uses decides whether CSRF applies.
        sessionCookie.set(reply, result.token, result.expiresAt);

        return reply.status(200).send(result.response);
      },
    }),

    defineRoute({
      method: 'POST',
      url: '/auth/logout',
      access: access.authenticated(),
      schema: {
        summary: 'Sign out',
        tags: TAGS,
        body: z.object({ everywhere: z.boolean().default(false) }),
        response: { 200: z.object({ revoked: z.number().int() }) },
      },
      async handler(request, reply) {
        const result = await auth.logout.execute({
          sessionId: SessionId.of(requireSessionId(request.auth.sessionId)),
          everywhere: request.body.everywhere,
        });

        // Revoking the row is the sign-out; clearing the cookie is tidiness.
        // Doing only the second leaves a copied token working until it expires.
        sessionCookie.clear(reply);

        return reply.status(200).send(result);
      },
    }),

    defineRoute({
      method: 'GET',
      url: '/auth/me',
      access: access.authenticated(),
      schema: {
        summary: 'The signed-in user',
        tags: TAGS,
        response: { 200: meResponseSchema, 401: problemSchema },
      },
      async handler(request, reply) {
        const userId = UserId.of(requireUserId(request.auth.userId));
        const user = await services.repositories.users.findById(userId);

        if (user === null) {
          throw new UnauthorizedError('Your session is no longer valid.', 'auth.session_invalid');
        }

        return reply.status(200).send({
          user: toAuthenticatedUser(user, request.auth.permissions),
          // Refreshed on every read so a long-lived tab always holds a token it
          // can spend on its next write.
          csrf_token: csrf.generate(requireSessionId(request.auth.sessionId)),
        });
      },
    }),

    defineRoute({
      method: 'GET',
      url: '/auth/sessions',
      access: access.authenticated(),
      schema: {
        summary: 'Where this account is signed in',
        tags: TAGS,
        response: { 200: sessionListResponseSchema },
      },
      async handler(request, reply) {
        const sessions = await auth.listSessions.execute({
          userId: UserId.of(requireUserId(request.auth.userId)),
          currentSessionId: SessionId.of(requireSessionId(request.auth.sessionId)),
        });

        return reply.status(200).send({ sessions });
      },
    }),

    defineRoute({
      method: 'DELETE',
      url: '/auth/sessions/:sessionId',
      access: access.authenticated(),
      schema: {
        summary: 'End one session',
        tags: TAGS,
        params: z.object({ sessionId: z.string().uuid() }),
        response: { 204: z.null(), 404: problemSchema },
      },
      async handler(request, reply) {
        await auth.revokeSession.execute({
          actorId: UserId.of(requireUserId(request.auth.userId)),
          sessionId: SessionId.parse(request.params.sessionId),
        });

        return reply.status(204).send();
      },
    }),

    defineRoute({
      method: 'POST',
      url: '/auth/verify-email',
      access: access.public(),
      rateLimit: 'auth.tokenRedeem',
      // The token comes from the recipient's inbox; a cross-site page cannot read it.
      csrfExempt: true,
      schema: {
        summary: 'Confirm an email address',
        tags: TAGS,
        body: verifyEmailRequestSchema,
        response: { 200: registerResponseSchema, 400: problemSchema },
      },
      async handler(request, reply) {
        const user = await auth.verifyEmail.execute({ token: request.body.token });

        return reply.status(200).send({ user });
      },
    }),

    defineRoute({
      method: 'POST',
      url: '/auth/resend-verification',
      access: access.public(),
      rateLimit: 'auth.emailDispatch',
      csrfExempt: true,
      schema: {
        summary: 'Send the confirmation link again',
        tags: TAGS,
        body: resendVerificationRequestSchema,
        response: { 202: acceptedResponseSchema },
      },
      async handler(request, reply) {
        await auth.resendVerification.execute({
          email: request.body.email,
          ip: request.context.clientIp,
        });

        return reply.status(202).send(ACCEPTED);
      },
    }),

    defineRoute({
      method: 'POST',
      url: '/auth/forgot-password',
      access: access.public(),
      rateLimit: 'auth.emailDispatch',
      csrfExempt: true,
      schema: {
        summary: 'Request a password reset link',
        tags: TAGS,
        body: forgotPasswordRequestSchema,
        response: { 202: acceptedResponseSchema },
      },
      async handler(request, reply) {
        await auth.forgotPassword.execute({
          email: request.body.email,
          ip: request.context.clientIp,
        });

        return reply.status(202).send(ACCEPTED);
      },
    }),

    defineRoute({
      method: 'POST',
      url: '/auth/reset-password',
      access: access.public(),
      rateLimit: 'auth.tokenRedeem',
      csrfExempt: true,
      schema: {
        summary: 'Set a new password using a mailed link',
        tags: TAGS,
        body: resetPasswordRequestSchema,
        response: {
          200: z.object({ revoked_sessions: z.number().int() }),
          400: problemSchema,
          422: problemSchema,
        },
      },
      async handler(request, reply) {
        const result = await auth.resetPassword.execute({
          token: request.body.token,
          password: request.body.password,
        });

        // Every session was revoked, this one included — the caller was not
        // signed in anyway, but a stale cookie would now point at nothing.
        sessionCookie.clear(reply);

        return reply.status(200).send({ revoked_sessions: result.revokedSessions });
      },
    }),

    defineRoute({
      method: 'POST',
      url: '/auth/change-password',
      access: access.authenticated(),
      rateLimit: 'auth.passwordChange',
      schema: {
        summary: 'Change your password',
        tags: TAGS,
        body: changePasswordRequestSchema,
        response: {
          200: z.object({ revoked_sessions: z.number().int() }),
          401: problemSchema,
          422: problemSchema,
        },
      },
      async handler(request, reply) {
        const result = await auth.changePassword.execute({
          userId: UserId.of(requireUserId(request.auth.userId)),
          currentSessionId: SessionId.of(requireSessionId(request.auth.sessionId)),
          currentPassword: request.body.current_password,
          newPassword: request.body.new_password,
        });

        return reply.status(200).send({ revoked_sessions: result.revokedSessions });
      },
    }),
  ];
}

/**
 * Narrows the nullable identity on a route that declared `authenticated`.
 *
 * The authorization hook has already rejected an anonymous caller by the time a
 * handler runs, but the type cannot know that. Throwing rather than asserting
 * means a route that loses its declaration fails loudly instead of reading
 * `null` as a user id.
 */
function requireUserId(userId: string | null): string {
  if (userId === null) {
    throw new UnauthorizedError('You need to sign in to do this.', 'auth.required');
  }

  return userId;
}

function requireSessionId(sessionId: string | null): string {
  if (sessionId === null) {
    throw new UnauthorizedError('You need to sign in to do this.', 'auth.required');
  }

  return sessionId;
}
