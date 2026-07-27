import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vuePlugin from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";
import mkcert from "vite-plugin-mkcert";
import path from "node:path";
import { nexoposVueRuntime } from "./vite-plugin-nexopos-vue.js";

/**
 * Shared Vite config factory for NexoPOS modules.
 *
 * Ensures module builds:
 * - rewrite `vue` imports to the shared NexoPOS runtime
 * - compile `.vue` SFCs
 * - emit assets under Public/build with a Laravel Vite manifest
 *
 * @param {object} options
 * @param {string} options.dirname Absolute path to the module root (usually from import.meta.url)
 * @param {string[]} options.inputs Vite entry paths relative to the module root
 * @param {number} [options.port=3332] Dev server port
 * @param {string} [options.base='/'] Vite base path
 * @param {Record<string, string>} [options.alias] Extra resolve aliases
 * @param {import('vite').Plugin[]} [options.plugins] Extra Vite plugins
 * @param {boolean} [options.https=true] Enable HTTPS via mkcert
 * @param {object} [options.server] Extra Vite server options
 * @param {object} [options.build] Extra Vite build options (merged)
 */
export function defineNexoPOSModuleConfig(options) {
    const {
        dirname,
        inputs,
        port = 3332,
        base = "/",
        alias = {},
        plugins = [],
        https = true,
        server = {},
        build = {},
    } = options;

    if (!dirname) {
        throw new Error("defineNexoPOSModuleConfig requires `dirname` (module root absolute path).");
    }

    if (!Array.isArray(inputs) || inputs.length === 0) {
        throw new Error("defineNexoPOSModuleConfig requires a non-empty `inputs` array.");
    }

    return ({ mode }) => {
        process.env = { ...process.env, ...loadEnv(mode, process.cwd()) };

        return defineConfig({
            base,
            server: {
                port,
                host: "127.0.0.1",
                cors: true,
                https,
                hmr: {
                    protocol: "wss",
                    host: "localhost",
                },
                ...server,
            },
            plugins: [
                nexoposVueRuntime(),
                vuePlugin({
                    template: {
                        transformAssetUrls: {
                            base: null,
                            includeAbsolute: false,
                        },
                    },
                }),
                ...(https ? [mkcert()] : []),
                tailwindcss(),
                laravel({
                    hotFile: "Public/hot",
                    input: inputs,
                    refresh: ["Resources/**"],
                }),
                ...plugins,
            ],
            resolve: {
                alias: {
                    "@": path.resolve(dirname, "Resources/ts"),
                    ...alias,
                },
            },
            build: {
                outDir: "Public/build",
                manifest: true,
                rollupOptions: {
                    input: inputs.map((entry) => `./${entry}`),
                },
                ...build,
            },
        });
    };
}

export { nexoposVueRuntime };
