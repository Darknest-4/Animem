const PREFIX = 'FEATURE_';

/** Names that configure the override mechanism rather than naming a flag. */
const RESERVED = new Set(['FEATURE_ENV_OVERRIDES']);

/**
 * Reads `FEATURE_<KEY>=on|off` out of the environment.
 *
 * `FEATURE_NEW_PLAYER=on` maps to the flag key `new.player`, which is the same
 * dotted shape the flag table uses.
 *
 * This lives in the config package because it is the only layer allowed to touch
 * `process.env` — every other package receives a typed object, so nothing
 * downstream can reach around configuration and read a variable directly.
 *
 * Overrides are refused in production by the caller: a flag flipped by an
 * environment variable leaves no audit entry, and "someone set it in the
 * container spec" is not an answer to "who turned this on?".
 */
export function readFeatureOverrides(source: NodeJS.ProcessEnv): ReadonlyMap<string, boolean> {
  const overrides = new Map<string, boolean>();

  for (const [name, value] of Object.entries(source)) {
    if (!name.startsWith(PREFIX) || RESERVED.has(name) || value === undefined) {
      continue;
    }

    const normalised = value.trim().toLowerCase();

    if (normalised !== 'on' && normalised !== 'off') {
      continue;
    }

    overrides.set(name.slice(PREFIX.length).toLowerCase().replace(/_/gu, '.'), normalised === 'on');
  }

  return overrides;
}
