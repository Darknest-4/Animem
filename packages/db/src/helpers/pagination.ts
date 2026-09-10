import { DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE, type PageRequest } from '@yume/core';

export interface LimitOffset {
  readonly limit: number;
  readonly offset: number;
}

/**
 * Converts a page request into SQL bounds, clamped.
 *
 * Clamping here rather than at each call site means one forgotten check cannot
 * turn `?per_page=1000000` into a table scan.
 */
export function toLimitOffset(request: Partial<PageRequest> = {}): LimitOffset {
  const page = Math.max(1, Math.trunc(request.page ?? 1));
  const perPage = Math.min(MAX_PAGE_SIZE, Math.max(1, Math.trunc(request.perPage ?? DEFAULT_PAGE_SIZE)));

  return { limit: perPage, offset: (page - 1) * perPage };
}
