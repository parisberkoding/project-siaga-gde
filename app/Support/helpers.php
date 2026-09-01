<?php

use Illuminate\Support\Str;

if (!function_exists('csp_nonce')) {
    /**
     * Generate a Content Security Policy nonce for inline scripts/styles.
     * Used in Blade templates: <script nonce="{{ csp_nonce() }}">
     */
    function csp_nonce(): string
    {
        return base64_encode(random_bytes(16));
    }
}
