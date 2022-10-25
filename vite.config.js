import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import fs from 'fs';
import vue from '@vitejs/plugin-vue';

export default ({ mode }) => {
    process.env = {...process.env, ...loadEnv(mode, process.cwd())};

    return defineConfig({
        server: {
            port: 3331,
            host: '127.0.0.1',
            hmr: {
                protocol: 'wss',
                host: 'localhost',
            },
            https: {
                key: fs.readFileSync( process.env.VITE_LOCAL_KEY ),
                cert: fs.readFileSync( process.env.VITE_LOCAL_CRT ),
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
                },
            ]
        },
        plugins: [
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
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
                refresh: [ 'resources/views/**', 'resources/ts/**', 'resources/sass/**' ],
            }),
        ],
    });
}