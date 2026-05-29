import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        vue({
            template: {
                compilerOptions: {
                    // Dire à Vue que emoji-picker est un web component natif
                    isCustomElement: (tag) => tag.includes('emoji-picker'),
                }
            }
        }),
        tailwindcss(),
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    build: {
        rollupOptions: {
            input: {
                app: 'resources/js/app.js',
            },
        },
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
