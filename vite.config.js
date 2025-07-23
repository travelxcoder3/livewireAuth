import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: true, // يسمح بالوصول من أي host
        port: 5173, // المنفذ الافتراضي لـ Vite
        hmr: {
            host: 'localhost', // أو يمكنك استخدام IP المحدد
        },
        cors: true, // تفعيل CORS
    },
});
