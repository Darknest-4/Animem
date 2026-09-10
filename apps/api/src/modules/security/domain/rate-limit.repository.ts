export interface RateLimitRepository {
  /**
   * Increments the bucket and returns the count *after* the increment.
   *
   * Must be atomic: two concurrent requests that both read 9 and both write 10
   * let eleven through a limit of ten, which is exactly the case a limiter
   * exists to stop. The Postgres implementation does it in one upsert.
   */
  consume(bucketKey: string, windowStart: Date, expiresAt: Date): Promise<number>;

  /** Reads the count without incrementing, for headers on an already-rejected request. */
  peek(bucketKey: string, windowStart: Date): Promise<number>;

  /** Housekeeping. Buckets are worthless the moment their window closes. */
  purgeExpired(now: Date): Promise<number>;
}
