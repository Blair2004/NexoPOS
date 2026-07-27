import * as VueExports from "vue";

const virtualModuleId = "virtual:nexopos-vue-runtime";
const resolvedVirtualModuleId = `\0${virtualModuleId}`;
const validExportName = /^[A-Za-z_$][A-Za-z0-9_$]*$/;

/**
 * Vite plugin that rewrites every `import … from 'vue'` in a module build to
 * the shared NexoPOS Vue runtime exposed by resources/ts/vue-runtime.ts
 * (`window.ns.vue` / `window.NexoPOSVue`).
 *
 * Modules keep normal Vue SFC + `import { createApp, ref } from 'vue'` DX while
 * sharing a single Vue object with core (no dual-Vue identity issues).
 */
export function nexoposVueRuntime() {
    return {
        name: "nexopos-vue-runtime",
        enforce: "pre",
        resolveId(source) {
            if (source === "vue" || source === "vue/dist/vue.esm-bundler" || source === "vue/dist/vue.esm-bundler.js") {
                return resolvedVirtualModuleId;
            }
        },
        load(id) {
            if (id !== resolvedVirtualModuleId) {
                return;
            }

            const exports = Object.keys(VueExports)
                .filter((name) => name !== "default" && validExportName.test(name))
                .map((name) => `export const ${name} = runtime[${JSON.stringify(name)}];`)
                .join("\n");

            return `
const runtime = globalThis.ns?.vue ?? globalThis.NexoPOSVue;

if (!runtime) {
    throw new Error(
        "The NexoPOS Vue runtime is unavailable. Ensure resources/ts/vue-runtime.ts is loaded (via the host layout) before module assets."
    );
}

if (import.meta.hot && !globalThis.__VUE_HMR_RUNTIME__) {
    globalThis.__VUE_HMR_RUNTIME__ = {
        createRecord() {
            return false;
        },
        rerender() {
            globalThis.location.reload();
        },
        reload() {
            globalThis.location.reload();
        },
    };
}

${exports}

/** Prefer window.nsCreateApp when available (registers core components). */
export function nsCreateApp(rootComponent, rootProps, options) {
    if (typeof globalThis.nsCreateApp === "function") {
        return globalThis.nsCreateApp(rootComponent, rootProps, options);
    }

    return runtime.createApp(rootComponent, rootProps);
}

export function nsRegisterComponent(name, component, options) {
    if (typeof globalThis.nsRegisterComponent === "function") {
        return globalThis.nsRegisterComponent(name, component, options);
    }

    throw new Error(
        "nsRegisterComponent is unavailable. Load resources/ts/vue-runtime.ts before module assets."
    );
}

export function nsRegisterComponentsOnApp(app, components) {
    if (typeof globalThis.nsRegisterComponentsOnApp === "function") {
        return globalThis.nsRegisterComponentsOnApp(app, components);
    }

    if (!components) {
        return app;
    }

    for (const [name, component] of Object.entries(components)) {
        if (name && component) {
            app.component(name, component);
        }
    }

    return app;
}

export default runtime;
`;
        },
    };
}
