import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    build: {
        reportCompressedSize: false,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }
                    if (id.includes('@inertiajs')) {
                        return 'inertia';
                    }
                    if (id.includes('node_modules/vue/') || id.includes('node_modules/@vue/')) {
                        return 'vue';
                    }
                    if (id.includes('@iconify')) {
                        return 'iconify';
                    }
                    if (
                        id.includes('radix-vue')
                        || id.includes('reka-ui')
                        || id.includes('class-variance-authority')
                        || id.includes('tailwind-merge')
                    ) {
                        return 'ui-kit';
                    }
                },
            },
        },
    },
});
