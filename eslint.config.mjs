// ESLint for the E2E test suite (e2e/) — see e2e/README.md. Kept separate from
// frontend/eslint.config.ts, which lints the Vue app and has its own
// package.json/node_modules.
import tseslint from 'typescript-eslint';
import playwright from 'eslint-plugin-playwright';

export default tseslint.config(
  {
    ignores: ['node_modules/**', 'playwright-report/**', 'test-results/**', 'frontend/**'],
  },
  {
    files: ['e2e/**/*.ts', 'playwright.config.ts'],
    extends: [tseslint.configs.recommended, playwright.configs['flat/recommended']],
    rules: {
      // Page Objects/fixtures are plain async helpers, not tests — some
      // playwright-plugin rules only make sense inside *.spec.ts files.
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
    },
  },
  {
    // Fixtures/support/pages are library code, not tests — expect()/test()
    // calls there are setup and teardown helpers, not assertions on
    // behavior-under-test, so the "always await expect" style rules that
    // matter inside specs are noise here.
    files: ['e2e/fixtures/**/*.ts', 'e2e/pages/**/*.ts', 'e2e/support/**/*.ts'],
    rules: {
      'playwright/expect-expect': 'off',
      'playwright/no-standalone-expect': 'off',
    },
  },
);
