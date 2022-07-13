import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        port: 3000,
        hmr: {
            protocol: 'ws',
            host: '127.0.0.1',
        },
    },
    resolve: {
        alias: [
            {
                find: '&',
                replacement: path.resolve( __dirname, 'resources' ),
            }, {
                find: '~',
                replacement: path.resolve( __dirname, 'resources/ts' ),
            }
        ]
    },
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    // The Vue plugin will re-write asset URLs, when referenced
                    // in Single File Components, to point to the Laravel web
                    // server. Setting this to `null` allows the Laravel plugin
                    // to instead re-write asset URLs to point to the Vite
                    // server instead.
                    base: null,
 
                    // The Vue plugin will parse absolute URLs and treat them
                    // as absolute paths to files on disk. Setting this to
                    // `false` will leave absolute URLs un-touched so they can
                    // reference assets in the public directly as expected.
                    includeAbsolute: false,
                },
            },
        }),
        laravel({
            input: [
                'resources/ts/app.ts',
                'resources/ts/auth.ts',
                'resources/ts/pos.ts',
                'resources/ts/pos-init.ts',
                'resources/ts/setup.ts',
                'resources/ts/update.ts',
                'resources/ts/dashboard.ts',

                'resources/css/app.css',
                'resources/css/light.css',
                'resources/css/dark.css',
            ],
            refresh: ['resources/views/**'],
        }),
    ],
});