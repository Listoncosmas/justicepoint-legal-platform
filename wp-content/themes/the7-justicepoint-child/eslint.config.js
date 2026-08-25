import js from '@eslint/js';

export default [js.configs.recommended, { files: ['assets/**/*.js'], languageOptions: { ecmaVersion: 2022, globals: { window: 'readonly', document: 'readonly' } } }];

