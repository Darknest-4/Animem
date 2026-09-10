/**
 * Named durations in seconds.
 *
 * Config files and policies read far better as `Duration.days(30)` than as
 * `2592000`, and a wrong constant is much harder to spot in the second form.
 */
export const Duration = Object.freeze({
  seconds: (n: number): number => n,
  minutes: (n: number): number => n * 60,
  hours: (n: number): number => n * 3600,
  days: (n: number): number => n * 86400,
});

export function addSeconds(date: Date, seconds: number): Date {
  return new Date(date.getTime() + seconds * 1000);
}

export function isPast(date: Date, now: Date): boolean {
  return date.getTime() <= now.getTime();
}
