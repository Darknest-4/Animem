import { Duration, type Clock } from '@yume/core';
import type { Logger } from '@yume/logger';

import type { FeatureFlagRepository } from '../domain/feature-flag.repository.js';
import { isEnabledFor, type FeatureFlag, type FlagContext } from '../domain/feature-flag.types.js';

/** How long a loaded snapshot is trusted before it is read again. */
const CACHE_TTL_MS = Duration.seconds(30) * 1000;

/**
 * Answers "is this flag on for this actor?".
 *
 * Cached for a few seconds because otherwise a page that checks six flags costs
 * six queries per request, and the cost of a flag check is what makes people
 * stop adding flags. A short TTL is the right trade: a rollout that takes half a
 * minute to reach every worker is fine; one that costs a query per check is not.
 */
export class FeatureFlagService {
  private snapshot: ReadonlyMap<string, FeatureFlag> = new Map();
  private loadedAt = 0;
  private inFlight: Promise<void> | null = null;

  constructor(
    private readonly repository: FeatureFlagRepository,
    private readonly clock: Clock,
    private readonly logger: Logger,
    /**
     * Flags forced on by environment variable, for local development.
     *
     * Refused in production by configuration, so a stray variable in a container
     * spec can never switch on something the flag table says is off.
     */
    private readonly overrides: ReadonlyMap<string, boolean> = new Map(),
  ) {}

  async isEnabled(key: string, context: FlagContext): Promise<boolean> {
    const override = this.overrides.get(key);

    if (override !== undefined) {
      return override;
    }

    await this.ensureFresh();

    const flag = this.snapshot.get(key);

    if (flag === undefined) {
      // An unknown key is off. A missing flag must never fail open — that turns
      // a typo in a route declaration into an ungated endpoint.
      this.logger.warn('Unknown feature flag requested.', { key });

      return false;
    }

    return isEnabledFor(flag, context, this.clock.now());
  }

  /** Every flag's state for this actor, for the client to render against. */
  async resolveAll(context: FlagContext): Promise<Readonly<Record<string, boolean>>> {
    await this.ensureFresh();

    const now = this.clock.now();
    const resolved: Record<string, boolean> = {};

    for (const [key, flag] of this.snapshot) {
      resolved[key] = this.overrides.get(key) ?? isEnabledFor(flag, context, now);
    }

    return resolved;
  }

  /** Drops the cache so an administrative change takes effect immediately. */
  invalidate(): void {
    this.loadedAt = 0;
  }

  private async ensureFresh(): Promise<void> {
    if (this.clock.now().getTime() - this.loadedAt < CACHE_TTL_MS) {
      return;
    }

    // Concurrent requests share one reload rather than each issuing their own,
    // which is what turns a cache expiry into a thundering herd.
    this.inFlight ??= this.reload().finally(() => {
      this.inFlight = null;
    });

    await this.inFlight;
  }

  private async reload(): Promise<void> {
    const flags = await this.repository.all();

    this.snapshot = new Map(flags.map((flag) => [flag.key, flag]));
    this.loadedAt = this.clock.now().getTime();
  }
}
