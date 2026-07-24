import * as VueRuntime from "vue/dist/vue.esm-bundler";

declare global {
    interface Window {
        NexoPOSVue: typeof VueRuntime;
    }
}

window.NexoPOSVue = VueRuntime;
window.ns.vue = VueRuntime;
