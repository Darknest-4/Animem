import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import prettier from 'eslint-config-prettier';
import importX from 'eslint-plugin-import-x';

/**
 * Rules shared by every package.
 *
 * The set is deliberately small and mostly about correctness, not taste —
 * formatting is Prettier's job and arguing about it in review is waste. The
 * exceptions are the import rules, which exist to keep the module graph honest:
 * a cycle between layers is the failure mode this architecture is built to avoid.
 */
export default tseslint.config(
  js.configs.recommended,
  ...tseslint.configs.strictTypeChecked,
  ...tseslint.configs.stylisticTypeChecked,
  {
    plugins: { 'import-x': importX },
    languageOptions: {
      parserOptions: {
        projectService: true,
      },
    },
    rules: {
      // Correctness
      '@typescript-eslint/no-floating-promises': 'error',
      '@typescript-eslint/no-misused-promises': 'error',
      '@typescript-eslint/switch-exhaustiveness-check': 'error',
      '@typescript-eslint/consistent-type-imports': [
        'error',
        { prefer: 'type-imports', fixStyle: 'inline-type-imports' },
      ],
      '@typescript-eslint/no-unused-vars': [
        'error',
        { argsIgnorePattern: '^_', varsIgnorePattern: '^_' },
      ],

      // Module graph
      'import-x/no-cycle': ['error', { maxDepth: 6 }],
      'import-x/no-self-import': 'error',
      'import-x/no-duplicates': 'error',

      // Escape hatches must be deliberate, not accidental
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/no-non-null-assertion': 'error',
      'no-console': ['error', { allow: ['warn', 'error'] }],
    },
  },
  prettier,
  {
    ignores: ['dist/**', '.next/**', '.turbo/**', 'coverage/**', '*.config.js'],
  },
);
