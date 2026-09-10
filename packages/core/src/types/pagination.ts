export const DEFAULT_PAGE_SIZE = 24;
export const MAX_PAGE_SIZE = 100;

export interface PageRequest {
  readonly page: number;
  readonly perPage: number;
}

export interface Paginated<T> {
  readonly items: readonly T[];
  readonly pagination: {
    readonly page: number;
    readonly perPage: number;
    readonly total: number;
    readonly totalPages: number;
  };
}

/**
 * Builds a page envelope from a slice and a total.
 *
 * Every list endpoint returns this shape. Defining it once means a client can
 * write one pagination component instead of one per resource — the legacy site
 * had four different list formats.
 */
export function toPaginated<T>(items: readonly T[], total: number, request: PageRequest): Paginated<T> {
  const perPage = Math.min(Math.max(1, request.perPage), MAX_PAGE_SIZE);

  return {
    items,
    pagination: {
      page: Math.max(1, request.page),
      perPage,
      total,
      totalPages: total === 0 ? 0 : Math.ceil(total / perPage),
    },
  };
}
