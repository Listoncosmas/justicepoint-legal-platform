import js from '@eslint/js';

export default [
  js.configs.recommended,
  { files: ['assets/**/*.js'], languageOptions: { ecmaVersion: 2022, sourceType: 'module', globals: { window: 'readonly', document: 'readonly', CSS: 'readonly', URLSearchParams: 'readonly', FormData: 'readonly', fetch: 'readonly' } } },
];

