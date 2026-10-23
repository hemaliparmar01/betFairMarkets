import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/admin/css/app.css',
                'resources/admin/js/app.js',
                'resources/users/css/app.css',
                'resources/users/css/auth.css',
                'resources/users/js/auth/app.js',
                'resources/users/js/app.js',
                'resources/users/js/sports/football.js',
                'resources/users/js/sports/cricket.js',
                'resources/users/js/sports/icehockey.js',
                'resources/users/js/sports/americanfootball.js',
                'resources/users/js/sports/baseball.js',
                'resources/users/js/sports/basketball.js',
                'resources/users/js/sports/tennis.js',
                'resources/users/js/sports/greyhounds.js',
                'resources/users/js/sports/horseracing.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
