import { AppError } from './app-error.js';

/** 400 — the request itself is malformed in a way schemas did not catch. */
export class BadRequestError extends AppError {
  readonly code: string;
  readonly status = 400;

  constructor(message: string, code = 'request.invalid', details?: Record<string, unknown>) {
    super(message, details);
    this.code = code;
  }
}

/** 401 — no identity, or an identity that no longer holds. */
export class UnauthorizedError extends AppError {
  readonly code: string;
  readonly status = 401;

  constructor(message = 'Authentication is required.', code = 'auth.required', details?: Record<string, unknown>) {
    super(message, details);
    this.code = code;
  }
}

/** 403 — a known identity that may not do this. */
export class ForbiddenError extends AppError {
  readonly code: string;
  readonly status = 403;

  constructor(message = 'You do not have permission to perform this action.', code = 'authorization.denied', details?: Record<string, unknown>) {
    super(message, details);
    this.code = code;
  }
}

/** 404 — including things hidden on purpose, which must not answer 403. */
export class NotFoundError extends AppError {
  readonly code: string;
  readonly status = 404;

  /**
   * @param resource A human-readable resource name, e.g. `feature flag`. The
   * machine code is derived from it, so spaces are folded to underscores: a
   * client matching on `code` should never have to handle `feature flag.not_found`.
   */
  constructor(resource: string, identifier?: string) {
    super(
      identifier === undefined ? `No such ${resource}.` : `No ${resource} "${identifier}".`,
      identifier === undefined ? { resource } : { resource, identifier },
    );
    this.code = `${resource.trim().toLowerCase().replace(/\s+/gu, '_')}.not_found`;
  }
}

/** 409 — a uniqueness or state conflict. */
export class ConflictError extends AppError {
  readonly code: string;
  readonly status = 409;

  constructor(message: string, code: string, details?: Record<string, unknown>) {
    super(message, details);
    this.code = code;
  }
}

/** 422 — the input is well-formed but violates a business rule. */
export class DomainRuleError extends AppError {
  readonly code: string;
  readonly status = 422;

  constructor(message: string, code = 'domain.rule_violated', details?: Record<string, unknown>) {
    super(message, details);
    this.code = code;
  }
}

/** 422 — field-level validation, carrying the per-field messages. */
export class ValidationError extends AppError {
  readonly code = 'validation.failed';
  readonly status = 422;

  constructor(issues: Record<string, readonly string[]>) {
    super('The submitted data is invalid.', { issues });
  }
}

/** 423 — temporarily locked, and the caller can wait it out. */
export class LockedError extends AppError {
  readonly code: string;
  readonly status = 423;

  constructor(message: string, code: string, retryAfterSeconds?: number) {
    super(message, retryAfterSeconds === undefined ? {} : { retryAfter: retryAfterSeconds });
    this.code = code;
  }
}

/** 429 — over budget. */
export class RateLimitError extends AppError {
  readonly code = 'rate_limit.exceeded';
  readonly status = 429;

  constructor(readonly retryAfterSeconds: number) {
    super('Too many requests. Please slow down.', { retryAfter: retryAfterSeconds });
  }
}

/** 503 — a dependency is down; the caller should retry, not change anything. */
export class ServiceUnavailableError extends AppError {
  readonly code = 'service.unavailable';
  readonly status = 503;

  // "postgres refused the connection" is operational detail, not caller detail.
  override readonly exposeMessage = false;
}
