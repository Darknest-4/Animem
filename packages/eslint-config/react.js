import base from './base.js';

export default [
  ...base,
  {
    rules: {
      // Server components make async render legitimate; the rule misfires.
      '@typescript-eslint/no-misused-promises': [
        'error',
        { checksVoidReturn: { attributes: false } },
      ],
    },
  },
];
