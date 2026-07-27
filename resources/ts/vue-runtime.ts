import * as VueRuntime from "vue/dist/vue.esm-bundler";
import type { App, Component } from "vue";

type NsCreateAppOptions = {
    /**
     * Register every component currently present on window.nsComponents.
     * Defaults to true so modules can use <ns-button>, <ns-field>, etc.
     */
    registerCoreComponents?: boolean;

    /**
     * Register every component currently present on window.nsExtraComponents.
     * Defaults to true so module/extra components already registered are available.
     */
    registerExtraComponents?: boolean;
};

type NsRegisterComponentOptions = {
    /**
     * App instance names on window to update live (when already created).
     * Defaults to the dashboard app instances.
     */
    apps?: string[];

    /**
     * Also store the component on window.nsComponents when it exists.
     * Defaults to true.
     */
    mirrorToNsComponents?: boolean;
};

declare global {
    interface Window {
        NexoPOSVue: typeof VueRuntime;
        nsCreateApp: typeof nsCreateApp;
        nsRegisterComponent: typeof nsRegisterComponent;
        nsRegisterComponentsOnApp: typeof nsRegisterComponentsOnApp;
        createApp: typeof VueRuntime.createApp;
        defineAsyncComponent: typeof VueRuntime.defineAsyncComponent;
        defineComponent: typeof VueRuntime.defineComponent;
        markRaw: typeof VueRuntime.markRaw;
        shallowRef: typeof VueRuntime.shallowRef;
        ns?: {
            vue?: typeof VueRuntime;
            nsExtraComponents?: Record<string, Component>;
            [key: string]: unknown;
        };
        nsComponents?: Record<string, Component>;
        nsExtraComponents?: Record<string, Component>;
        nsDashboardContent?: App;
        nsDashboardHeader?: App;
        nsDashboardAside?: App;
        nsDashboardOverlay?: App;
    }
}

const defaultDashboardApps = [
    "nsDashboardContent",
    "nsDashboardHeader",
    "nsDashboardAside",
    "nsDashboardOverlay",
];

/**
 * Register a map of components on a Vue app instance.
 */
export function nsRegisterComponentsOnApp(
    app: App,
    components?: Record<string, Component> | null
): App {
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

/**
 * Create a Vue application that always uses the shared NexoPOS Vue runtime.
 * Optionally registers core and extra components so SFCs can use them as tags.
 */
export function nsCreateApp(
    rootComponent?: Component,
    rootProps?: Record<string, unknown> | null,
    options: NsCreateAppOptions = {}
): App<Element> {
    const {
        registerCoreComponents = true,
        registerExtraComponents = true,
    } = options;

    const app = VueRuntime.createApp(
        rootComponent as Component,
        rootProps ?? undefined
    );

    if (registerCoreComponents) {
        nsRegisterComponentsOnApp(app, window.nsComponents);
    }

    if (registerExtraComponents) {
        nsRegisterComponentsOnApp(app, window.nsExtraComponents);
    }

    return app;
}

/**
 * Register a component for dashboard injection and, when the apps already
 * exist, on those live app instances as well.
 *
 * Prefer calling this from module assets injected before app-init mounts
 * (footer injection). Late registration still updates live apps when present.
 */
export function nsRegisterComponent(
    name: string,
    component: Component,
    options: NsRegisterComponentOptions = {}
): Component {
    if (!name) {
        throw new Error("nsRegisterComponent requires a non-empty component name.");
    }

    if (!window.nsExtraComponents) {
        window.nsExtraComponents = {};
    }

    window.nsExtraComponents[name] = component;

    if (options.mirrorToNsComponents !== false && window.nsComponents) {
        window.nsComponents[name] = component;
    }

    const apps = options.apps ?? defaultDashboardApps;

    for (const appName of apps) {
        const app = (window as Record<string, unknown>)[appName] as App | undefined;

        if (app && typeof app.component === "function") {
            app.component(name, component);
        }
    }

    return component;
}

// Ensure the host ns object exists before attaching the runtime.
window.ns = window.ns ?? { nsExtraComponents: window.nsExtraComponents ?? {} };

window.NexoPOSVue = VueRuntime;
window.ns.vue = VueRuntime;

// Legacy globals: always the same object module as NexoPOSVue.
window.createApp = VueRuntime.createApp;
window.defineAsyncComponent = VueRuntime.defineAsyncComponent;
window.defineComponent = VueRuntime.defineComponent;
window.markRaw = VueRuntime.markRaw;
window.shallowRef = VueRuntime.shallowRef;

// Module-facing helpers (also available after this script loads).
window.nsCreateApp = nsCreateApp;
window.nsRegisterComponent = nsRegisterComponent;
window.nsRegisterComponentsOnApp = nsRegisterComponentsOnApp;

export type { NsCreateAppOptions, NsRegisterComponentOptions };
export type { App, Component };
export default VueRuntime;
