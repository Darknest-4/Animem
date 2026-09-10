/**
 * Time as an injected dependency, never as a global side effect.
 *
 * Domain code depends on this instead of calling Date.now(), so expiry, rollout
 * bucketing and rate-limit windows are deterministically testable.
 */
export interface Clock {
  now(): Date;
  timestamp(): number;
}

export const systemClock: Clock = Object.freeze({
  now: (): Date => new Date(),
  timestamp: (): number => Date.now(),
});

/** Test double: advance time explicitly rather than sleeping. */
export class FixedClock implements Clock {
  #current: Date;

  constructor(start: Date | string = '2026-01-01T00:00:00.000Z') {
    this.#current = typeof start === 'string' ? new Date(start) : new Date(start.getTime());
  }

  now(): Date {
    return new Date(this.#current.getTime());
  }

  timestamp(): number {
    return this.#current.getTime();
  }

  advance(seconds: number): void {
    this.#current = new Date(this.#current.getTime() + seconds * 1000);
  }

  set(value: Date | string): void {
    this.#current = typeof value === 'string' ? new Date(value) : new Date(value.getTime());
  }
}
