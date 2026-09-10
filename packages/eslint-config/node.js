import base from './base.js';

/** Node services: the same rules, plus a ban on the globals that hide bugs. */
export default [
  ...base,
  {
    rules: {
      // Aimed at `process.env` specifically, not at `process`. Banning the whole
      // global would also rule out `process.uptime()` in a liveness probe and
      // `process.stderr` in the crash path — neither of which is configuration,
      // and both of which have no typed alternative.
      'no-restricted-properties': [
        'error',
        {
          object: 'process',
          property: 'env',
          message: 'Read configuration through @yume/config, which validates it, rather than process.env.',
        },
      ],
    },
  },
  {
    // The config package is the one place allowed to read the environment.
    files: ['**/config/**', '**/*.config.ts', '**/env.ts', '**/scripts/**'],
    rules: { 'no-restricted-properties': 'off' },
  },
];
