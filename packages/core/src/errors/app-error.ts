/**
 * The single error hierarchy for the whole system.
 *
 * Every expected failure carries an HTTP status and a stable machine-readable
 * code, so the HTTP layer can translate any of them without a switch statement
 * that has to be extended for each new error. Anything that is *not* an AppError
 * is by definition unexpected and becomes an opaque 500.
 */
export abstract class AppError extends Error {
  abstract readonly code: string;
  abstract readonly status: number;

  /** Extra fields merged into the problem response and the log record. */
  readonly details: Readonly<Record<string, unknown>>;

  constructor(message: string, details: Record<string, unknown> = {}, options?: ErrorOptions) {
    super(message, options);
    this.name = new.target.name;
    this.details = Object.freeze({ ...details });

    // Without this the stack trace of a subclass points at this constructor.
    // V8-only, and typed as always present, so the availability check is a
    // runtime one for non-V8 runtimes rather than something the types express.
    if (typeof Error.captureStackTrace === 'function') {
      Error.captureStackTrace(this, new.target);
    }
  }

  /**
   * Whether the message is safe to show a caller.
   *
   * Subclasses describe rule violations the caller caused, so the default is
   * yes. An error that could leak internals overrides it with its own field.
   */
  readonly exposeMessage: boolean = true;
}

/** Type guard used by the error handler and by tests. */
export function isAppError(error: unknown): error is AppError {
  return error instanceof AppError;
}
