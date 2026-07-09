import js from "@eslint/js";
import globals from "globals";

export default [
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 2017,
            globals: {
                // Browser globals
                ...globals.browser,
                // jQuery
                $: "readonly",
                jQuery: "readonly",
                // PrestaShop globals
                prestashop: "readonly",
                // Payplug globals injected via PHP
                payplug_ajax_url: "readonly",
                payplug_errors: "readonly",
                payplug_transaction_error_message: "readonly",
                is_sandbox_mode: "readonly",
                isIntegratedPayment: "readonly",
                check_errors: "readonly",
                card_confirm_deleted_msg: "readonly",
                card_deleted_msg: "readonly",
                applePayMerchantSessionAjaxURL: "readonly",
                applePayPaymentRequestAjaxURL: "readonly",
                applePayIdCart: "readonly",
                placeholderCardholder: "readonly",
                placeholderPan: "readonly",
                placeholderCvv: "readonly",
                placeholderExp: "readonly",
                $oneyType: "writable",
                // Payplug JS SDK
                Payplug: "readonly",
                ApplePaySession: "readonly",
                // Official Oney widget (loaded at runtime from Oney's own loader script)
                oneyMerchantApp: "readonly",
                // Functions/variables defined across JS files (writable = assigned without var)
                callCapture: "writable",
                payplug_utilities: "writable",
                sanitizePopupHtml: "writable",
                getHtmlTags: "writable",
                allTags: "writable",
                tags: "writable",
                addLogger: "writable",
                form: "writable",
                token: "writable",
                html: "writable",
                i: "writable",
                integratedPayment: "writable",
                integratedPaymentError: "writable",
            },
        },
        rules: {
            "no-unused-vars": "warn",
            "no-undef": "error",
            "no-console": "warn",
            "no-var": "warn",
            // Style rules — warn only (legacy code)
            "eqeqeq": "warn",
            "semi": ["warn", "always"],
            "no-prototype-builtins": "warn",
            "no-constant-binary-expression": "warn",
            "valid-typeof": "warn",
            "no-case-declarations": "warn",
            "no-redeclare": "warn",
        },
    },
    {
        ignores: [
            "vendor/**",
            "node_modules/**",
            "dev/js/app.js",
            "dev/js/chunk-vendors.js",
            "assets/**",
        ],
    },
];







