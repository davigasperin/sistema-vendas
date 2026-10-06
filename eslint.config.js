import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    { ignores: ['node_modules/**', 'public/build/**', 'vendor/**', 'storage/**', '.planning/**'] },

    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],

    {
        files: ['**/*.vue'],
        languageOptions: {
            parserOptions: {
                parser: tseslint.parser,
            },
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                fetch: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                Intl: 'readonly',
                Date: 'readonly',
                Math: 'readonly',
                JSON: 'readonly',
                FormData: 'readonly',
                KeyboardEvent: 'readonly',
                HTMLInputElement: 'readonly',
                HTMLTextAreaElement: 'readonly',
                HTMLSelectElement: 'readonly',
                HTMLElement: 'readonly',
                Event: 'readonly',
                Blob: 'readonly',
                URL: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'warn',
            'vue/no-template-target-blank': 'error',
            'vue/require-default-prop': 'off',
            'vue/require-explicit-emits': 'off',
            'vue/html-self-closing': ['error', {
                html: { void: 'always', normal: 'always', component: 'always' },
                svg: 'always',
                math: 'always',
            }],
        },
    },

    {
        files: ['**/*.ts', '**/*.js'],
        languageOptions: {
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                fetch: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                Intl: 'readonly',
                Date: 'readonly',
                Math: 'readonly',
                JSON: 'readonly',
                FormData: 'readonly',
            },
        },
        rules: {
            '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
            '@typescript-eslint/no-explicit-any': 'warn',
        },
    }
);
