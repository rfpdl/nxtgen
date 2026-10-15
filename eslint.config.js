import js from '@eslint/js'
import globals from 'globals'
import pluginVue from 'eslint-plugin-vue'
import configPrettier from 'eslint-config-prettier'
import tseslint from 'typescript-eslint'

export default tseslint.config(
    { ignores: ['public/**', 'vendor/**', 'node_modules/**', 'bootstrap/ssr/**', 'storage/**'] },

    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],

    {
        files: ['**/*.{ts,vue}'],
        languageOptions: {
            // This is a browser application: `document`, `window`, `localStorage` and
            // `KeyboardEvent` are all real here. Without this, `no-undef` reports them as
            // undefined — which it did first in a vendored shadcn component, making a correct file
            // look broken.
            globals: globals.browser,
            parserOptions: {
                parser: tseslint.parser,
                ecmaVersion: 'latest',
                sourceType: 'module',
            },
        },
        rules: {
            // `any` defeats the point of the generated types coming out of
            // `php artisan typescript:transform`. An unavoidable one is argued for in a comment
            // with `@ts-expect-error`, which at least fails when it stops being necessary.
            '@typescript-eslint/no-explicit-any': 'error',
            '@typescript-eslint/consistent-type-imports': ['error', { prefer: 'type-imports' }],
            '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],

            // Props are declared with `defineProps<{…}>()`, so the runtime rule has nothing to read.
            'vue/require-default-prop': 'off',

            // Multi-word names would rename atoms to `BaseAppButton`. The atoms/ prefix convention
            // already prevents collisions with HTML elements.
            'vue/multi-word-component-names': 'off',
        },
    },

    configPrettier,
)
