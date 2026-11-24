import eslint from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import eslintPluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import typescriptEslint from 'typescript-eslint';

export default typescriptEslint.config(
    {
        ignores: ['node_modules/', 'vendor/', 'public/build/', 'bootstrap/ssr/', 'storage/'],
    },
    {
        files: ['**/*.{js,mjs,cjs,ts,vue}'],
        extends: [eslint.configs.recommended, ...typescriptEslint.configs.recommended, ...eslintPluginVue.configs['flat/recommended']],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
            },
            parserOptions: {
                parser: typescriptEslint.parser,
            },
        },
    },
    eslintConfigPrettier,
);
