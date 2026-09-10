/**
 * A nominal type built on a structural one.
 *
 * `type UserId = Brand<string, 'UserId'>` makes a UserId unassignable to an
 * AnimeId even though both are strings at runtime, which is what stops the
 * classic bug of passing the wrong id into a lookup that happily accepts it.
 */
declare const brand: unique symbol;

export type Brand<T, K extends string> = T & { readonly [brand]: K };

/** Strips the brand, for the boundary where a value leaves the type system. */
export type Unbrand<T> = T extends Brand<infer U, string> ? U : T;
