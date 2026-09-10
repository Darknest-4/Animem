import { pino, type Logger as PinoLogger, type LoggerOptions } from 'pino';

/**
 * The logging surface the rest of the system depends on.
 *
 * Deliberately narrower than pino's own: everything goes through `child()` for
 * context, and there is no `log(level, ...)` escape hatch that would let a
 * caller invent levels.
 */
export interface Logger {
  trace(message: string, context?: Record<string, unknown>): void;
  debug(message: string, context?: Record<string, unknown>): void;
  info(message: string, context?: Record<string, unknown>): void;
  warn(message: string, context?: Record<string, unknown>): void;
  error(message: string, context?: Record<string, unknown>): void;
  fatal(message: string, context?: Record<string, unknown>): void;
  child(context: Record<string, unknown>): Logger;
}

export type LogLevel = 'trace' | 'debug' | 'info' | 'warn' | 'error' | 'fatal' | 'silent';

export interface LoggerConfig {
  readonly level: LogLevel;
  readonly pretty: boolean;
  readonly name: string;
  readonly version?: string;
}

/**
 * Field names whose values never belong in a log store.
 *
 * pino redacts by path, so nested occurrences are listed explicitly. The cost of
 * an over-broad list is an unhelpful log line; the cost of a narrow one is a
 * password in a search index.
 */
const REDACTED_PATHS = [
  'password',
  'currentPassword',
  'newPassword',
  'token',
  'sessionToken',
  'refreshToken',
  'secret',
  'authorization',
  'cookie',
  'apiKey',
  '*.password',
  '*.token',
  '*.secret',
  'req.headers.authorization',
  'req.headers.cookie',
  'res.headers["set-cookie"]',
  'body.password',
  'body.new_password',
  'body.current_password',
  'body.token',
];

function toPinoOptions(config: LoggerConfig): LoggerOptions {
  const base: LoggerOptions = {
    level: config.level,
    name: config.name,
    base: config.version === undefined ? {} : { version: config.version },
    redact: { paths: REDACTED_PATHS, censor: '[redacted]' },
    formatters: {
      // `level: "info"` reads better in a log viewer than `level: 30`.
      level: (label) => ({ level: label }),
    },
    timestamp: pino.stdTimeFunctions.isoTime,
  };

  if (!config.pretty) {
    return base;
  }

  return {
    ...base,
    transport: {
      target: 'pino-pretty',
      options: { colorize: true, translateTime: 'HH:MM:ss.l', ignore: 'pid,hostname' },
    },
  };
}

class PinoAdapter implements Logger {
  constructor(private readonly inner: PinoLogger) {}

  trace(message: string, context: Record<string, unknown> = {}): void {
    this.inner.trace(context, message);
  }

  debug(message: string, context: Record<string, unknown> = {}): void {
    this.inner.debug(context, message);
  }

  info(message: string, context: Record<string, unknown> = {}): void {
    this.inner.info(context, message);
  }

  warn(message: string, context: Record<string, unknown> = {}): void {
    this.inner.warn(context, message);
  }

  error(message: string, context: Record<string, unknown> = {}): void {
    this.inner.error(context, message);
  }

  fatal(message: string, context: Record<string, unknown> = {}): void {
    this.inner.fatal(context, message);
  }

  child(context: Record<string, unknown>): Logger {
    return new PinoAdapter(this.inner.child(context));
  }
}

export function createLogger(config: LoggerConfig): Logger {
  return new PinoAdapter(pino(toPinoOptions(config)));
}

/** Exposed so Fastify can be handed the same instance rather than a second one. */
export function createPinoInstance(config: LoggerConfig): PinoLogger {
  return pino(toPinoOptions(config));
}

/** Collects records in memory. For tests that assert on what was logged. */
export class MemoryLogger implements Logger {
  readonly records: { level: LogLevel; message: string; context: Record<string, unknown> }[] = [];

  constructor(private readonly bound: Record<string, unknown> = {}) {}

  private push(level: LogLevel, message: string, context: Record<string, unknown>): void {
    this.records.push({ level, message, context: { ...this.bound, ...context } });
  }

  trace(message: string, context: Record<string, unknown> = {}): void {
    this.push('trace', message, context);
  }

  debug(message: string, context: Record<string, unknown> = {}): void {
    this.push('debug', message, context);
  }

  info(message: string, context: Record<string, unknown> = {}): void {
    this.push('info', message, context);
  }

  warn(message: string, context: Record<string, unknown> = {}): void {
    this.push('warn', message, context);
  }

  error(message: string, context: Record<string, unknown> = {}): void {
    this.push('error', message, context);
  }

  fatal(message: string, context: Record<string, unknown> = {}): void {
    this.push('fatal', message, context);
  }

  child(context: Record<string, unknown>): Logger {
    const child = new MemoryLogger({ ...this.bound, ...context });
    // Share the buffer so assertions can read every record from the root.
    Object.defineProperty(child, 'records', { value: this.records });

    return child;
  }
}
