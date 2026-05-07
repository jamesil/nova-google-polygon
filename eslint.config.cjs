const js = require('@eslint/js');
const vue = require('eslint-plugin-vue');
const globals = require('globals');
const prettier = require('eslint-config-prettier/flat');

module.exports = [
    js.configs.recommended,
    ...vue.configs['flat/essential'],
    prettier,
    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 2018,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                Nova: 'writable',
            },
        },
        rules: {
            'vue/html-indent': ['error', 4],
        },
    },
];
