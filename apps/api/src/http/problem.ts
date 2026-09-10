import { isAppError } from '@yume/core';

/**
 * RFC 9457 problem responses.
 *
 * One shape for every failure the API can produce, so a client never has to
 * guess whether an error arrived as a string, an object or an HTML stack trace —
 * the site this replaces returned all three depending on which file handled the
 * request.
 */
export interface ProblemDetails {
  readonly type: string;
  readonly title: string;
  readonly status: number;
  readonly code: string;
  readonly detail: string;
  readonly requestId?: string;
  readonly [extension: string]: unknown;
}

const TITLES: Readonly<Record<number, string>> = Object.freeze({
  400: 'Bad Request',
  401: 'Unauthorized',
  403: 'Forbidden',
  404: 'Not Found',
  405: 'Method Not Allowed',
  409: 'Conflict',
  413: 'Payload Too Large',
  415: 'Unsupported Media Type',
  422: 'Unprocessable Content',
  423: 'Locked',
  428: 'Precondition Required',
  429: 'Too Many Requests',
  500: 'Internal Server Error',
  503: 'Service Unavailable',
});

export const PROBLEM_CONTENT_TYPE = 'application/problem+json; charset=utf-8';

export function problem(
  status: number,
  code: string,
  detail: string,
  extensions: Record<string, unknown> = {},
  requestId?: string,
): ProblemDetails {
  return {
    type: `https://yume.dev/problems/${code}`,
    title: TITLES[status] ?? 'Error',
    status,
    code,
    detail,
    ...(requestId === undefined ? {} : { requestId }),
    ...extensions,
  };
}

/**
 * Turns any thrown value into a problem response.
 *
 * An AppError is expected and describes something the caller did; anything else
 * is a fault and becomes an opaque 500 whose detail lives only in the log.
 */
export function toProblem(error: unknown, options: { requestId?: string; debug: boolean }): ProblemDetails {
  if (isAppError(error)) {
    return problem(
      error.status,
      error.code,
      error.exposeMessage ? error.message : 'The request could not be completed.',
      error.details,
      options.requestId,
    );
  }

  return problem(
    500,
    'server.error',
    'An unexpected error occurred.',
    options.debug
      ? {
          debug: {
            name: error instanceof Error ? error.name : typeof error,
            message: error instanceof Error ? error.message : String(error),
            stack: error instanceof Error ? error.stack?.split('\n').slice(0, 8) : undefined,
          },
        }
      : {},
    options.requestId,
  );
}

/** Headers a problem response should carry beyond the content type. */
export function problemHeaders(error: unknown): Record<string, string> {
  if (!isAppError(error)) {
    return {};
  }

  const retryAfter = error.details['retryAfter'];

  return typeof retryAfter === 'number' ? { 'retry-after': String(retryAfter) } : {};
}
