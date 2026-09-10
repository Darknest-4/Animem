import { addSeconds, RateLimitError, type Clock } from '@yume/core';
import type { Logger } from '@yume/logger';

import type { RateLimitPolicyName } from '../../../http/route.js';
import type { RateLimitRepository } from '../domain/rate-limit.repository.js';
import {
  bucketKeyFor,
  RATE_LIMIT_POLICIES,
  windowStartFor,
  type RateLimitDecision,
  type RateLimitSubject,
} from '../domain/rate-limit.types.js';

/**
 * The one place a request is counted against a budget.
 *
 * Consumption happens exactly once per request, in the rate-limit hook. Handlers
 * never call `consume` — in the codebase this replaces, the middleware and the
 * handler each charged the same budget, so every attempt cost two and the
 * effective limit was half of the configured one, which nobody noticed until the
 * limits looked mysteriously strict.
 */
export class RateLimiter {
  constructor(
    private readonly repository: RateLimitRepository,
    private readonly clock: Clock,
    private readonly logger: Logger,
  ) {}

  async consume(policyName: RateLimitPolicyName, subject: RateLimitSubject): Promise<RateLimitDecision> {
    const policy = RATE_LIMIT_POLICIES[policyName];
    const now = this.clock.now();
    const windowStart = windowStartFor(now, policy.windowSeconds);
    const resetAt = addSeconds(windowStart, policy.windowSeconds);
    const key = bucketKeyFor(policyName, policy.scope, subject);

    const hits = await this.repository.consume(key, windowStart, resetAt);
    const allowed = hits <= policy.limit;

    if (!allowed) {
      this.logger.warn('Rate limit exceeded.', {
        policy: policyName,
        ip: subject.ip,
        hits,
        limit: policy.limit,
      });
    }

    return {
      allowed,
      limit: policy.limit,
      remaining: Math.max(0, policy.limit - hits),
      resetAt,
      retryAfterSeconds: Math.max(1, Math.ceil((resetAt.getTime() - now.getTime()) / 1000)),
    };
  }

  /** @throws RateLimitError when the budget is spent. */
  async enforce(policyName: RateLimitPolicyName, subject: RateLimitSubject): Promise<RateLimitDecision> {
    const decision = await this.consume(policyName, subject);

    if (!decision.allowed) {
      throw new RateLimitError(decision.retryAfterSeconds);
    }

    return decision;
  }

  /** Standard advisory headers, set on every rate-limited response. */
  static headers(decision: RateLimitDecision): Record<string, string> {
    return {
      'ratelimit-limit': String(decision.limit),
      'ratelimit-remaining': String(decision.remaining),
      'ratelimit-reset': String(Math.max(0, Math.ceil((decision.resetAt.getTime() - Date.now()) / 1000))),
    };
  }
}
