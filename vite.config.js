import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Note: intentionally not using laravel-vite-plugin's remote font fetching
// (e.g. the `bunny()` helper) — it calls out to fonts.bunny.net during
// `npm run build`, which makes the production Docker build depend on
// third-party network access at build time. System font stack is used
// instead (see resources/css/app.css).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
