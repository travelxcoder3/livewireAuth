// ملف إعدادات التطوير مع host مخصص
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
        host: '192.168.30.9', // IP المحدد
        port: 5173, // منفذ Vite الافتراضي
        hmr: {
            host: '192.168.30.9',
            port: 8000,
        },
        cors: true,
        strictPort: false, // استخدام منفذ بديل إذا كان مشغول
    },
    base: '/', // التأكد من المسار الأساسي
});
