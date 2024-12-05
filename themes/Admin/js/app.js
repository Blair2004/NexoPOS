/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";

/**
 * We will create a fresh Vue application instance.
 */
// import { createApp } from "vue";
import { createApp } from "vue/dist/vue.esm-bundler.js";

const app = createApp({});

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = import.meta.globEager("./components/*.vue");
// for (const key in files) {
//     app.component(key.split("/").pop().split(".")[0], files[key].default);
// }

import ExampleComponent from "./components/ExampleComponent.vue";

app.component("example-component", ExampleComponent);

/**
 * Next, attach Vue application instance to the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
app.mount("#app");
