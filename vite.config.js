import { defineConfig, loadEnv } from 'vite';

// import fs from 'fs';
import laravel from 'laravel-vite-plugin';
import mkcert from 'vite-plugin-mkcert';
import { resolve } from 'path';
// import path from 'path';
import vuePlugin from '@vitejs/plugin-vue';
import tailwindcss from "@tailwindcss/vite";


export default ({ mode }) => {
    process.env = {...process.env, ...loadEnv(mode, process.cwd())};

    return defineConfig({
        base: './',
        server: {
            port: 3331,
            host: '127.0.0.1',
            hmr: {
                protocol: 'wss',
                host: 'localhost',
            },
            https: true,
        },
        resolve: {
            alias: [
                {
                    find: '&',
                    replacement: resolve( __dirname, 'resources' ),
                }, {
                    find: '~',
                    replacement: resolve( __dirname, 'resources/ts' ),
                },
            ]
        },
        plugins: [
            tailwindcss(),
            laravel({
                input: [
                    'resources/ts/bootstrap.ts',
                    'resources/ts/app-init.ts',
                    'resources/ts/app.ts',
                    'resources/ts/auth.ts',
                    'resources/ts/pos.ts',
                    'resources/ts/pos-init.ts',
                    'resources/ts/setup.ts',
                    'resources/ts/update.ts',
                    'resources/ts/cashier.ts',
                    'resources/ts/lang-loader.ts',
                    'resources/ts/dev.ts',
                    'resources/ts/popups.ts',
                    'resources/ts/widgets.ts',
                    'resources/ts/wizard.ts',
    
                    'resources/css/app.css',
                    'resources/css/grid.css',
                    'resources/css/animations.css',
                    'resources/css/fonts.css',
                    'resources/scss/line-awesome/1.3.0/scss/line-awesome.scss',

                    // themes
                    'resources/css/light.css',
                    'resources/css/dark.css',
                    'resources/css/phosphor.css',
                ],
                refresh: true,
            }),
            mkcert(),
            vuePlugin({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
    });
}