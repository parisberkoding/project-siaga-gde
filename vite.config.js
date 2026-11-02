import { defineConfig } from 'vite';
import { globSync } from 'node:fs';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // Satu entry per halaman di resources/js/pages, dimuat lewat @vite di view masing-masing.
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/legacy-shell.css',
                'resources/js/legacy-shell.js',
                ...globSync('resources/js/pages/**/*.js'),
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
