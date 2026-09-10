import { DomainRuleError } from '../errors/index.js';
import type { Brand } from '../types/brand.js';
import { isUuid, uuidV7 } from './uuid.js';

/**
 * A factory for branded UUID identifiers.
 *
 * The PHP version of this codebase had five near-identical value objects —
 * UserId, SessionId, AnimeId, EpisodeId, UploaderId — each ~30 lines of the same
 * regex and the same equals(). This replaces all of them:
 *
 *   export const UserId = defineId<'UserId'>('user');
 *   export type UserId = IdOf<typeof UserId>;
 *
 * The brand still keeps a UserId from being passed where an AnimeId belongs,
 * so nothing is lost in safety — only in duplication.
 */
export interface IdFactory<K extends string> {
  /** Parses an untrusted string; throws if it is not a UUID. */
  readonly parse: (value: string) => Brand<string, K>;
  /** Parses without throwing; null when invalid. */
  readonly safeParse: (value: string | null | undefined) => Brand<string, K> | null;
  /** Mints a new identifier. */
  readonly generate: () => Brand<string, K>;
  /** Asserts a value already known to be valid, e.g. straight out of the database. */
  readonly of: (value: string) => Brand<string, K>;
  readonly resourceName: string;
}

export type IdOf<F> = F extends IdFactory<infer K> ? Brand<string, K> : never;

export function defineId<K extends string>(resourceName: string): IdFactory<K> {
  type Id = Brand<string, K>;

  const parse = (value: string): Id => {
    if (!isUuid(value)) {
      throw new DomainRuleError(`${resourceName} id must be a UUID.`, `${resourceName}.invalid_id`, {
        received: value.slice(0, 64),
      });
    }

    return value.toLowerCase() as Id;
  };

  return Object.freeze({
    resourceName,
    parse,
    safeParse: (value: string | null | undefined): Id | null =>
      typeof value === 'string' && isUuid(value) ? (value.toLowerCase() as Id) : null,
    generate: (): Id => uuidV7() as Id,
    of: (value: string): Id => value.toLowerCase() as Id,
  });
}
