import type { UserId } from '../../users/domain/user.types.js';
import type { FeatureFlag } from './feature-flag.types.js';

export interface FeatureFlagUpdate {
  readonly strategy?: FeatureFlag['strategy'];
  readonly rolloutPercentage?: number;
  readonly payload?: FeatureFlag['payload'];
  readonly expiresAt?: Date | null;
}

export interface FeatureFlagRepository {
  all(): Promise<readonly FeatureFlag[]>;
  findByKey(key: string): Promise<FeatureFlag | null>;
  /**
   * Applies a change and writes the before/after pair to the audit table.
   *
   * One method rather than an update plus a separate audit call, because the
   * audit entry that gets forgotten is always the one for the change that
   * mattered.
   */
  update(key: string, changes: FeatureFlagUpdate, changedBy: UserId | null): Promise<FeatureFlag>;
}
