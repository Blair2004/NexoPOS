import * as VueExports from "vue";

const virtualModuleId = "virtual:nexopos-vue-runtime";
const resolvedVirtualModuleId = `\0${virtualModuleId}`;
const validExportName = /^[A-Za-z_$][A-Za-z0-9_$]*$/;

export function nexoposVueRuntime() {
    return {
        name: "nexopos-vue-runtime",
        enforce: "pre",
        resolveId(source) {
            if (source === "vue") {
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
    throw new Error("The NexoPOS Vue runtime is unavailable. Load resources/ts/vue-runtime.ts before module assets.");
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
export default runtime;
`;
        },
    };
}
