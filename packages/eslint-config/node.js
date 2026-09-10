import base from './base.js';

/** Node services: the same rules, plus a ban on the globals that hide bugs. */
export default [
  ...base,
  {
    rules: {
      'no-restricted-globals': [
        'error',
        { name: 'process', message: 'Import the typed config from @yume/config instead of reading process.env.' },
      ],
    },
  },
  {
    // The config package is the one place allowed to read the environment.
    files: ['**/config/**', '**/*.config.ts', '**/env.ts'],
    rules: { 'no-restricted-globals': 'off' },
  },
];
