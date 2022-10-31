import { createApp } from "vue";
import { createRouter, createWebHashHistory, RouteRecordRaw } from "vue-router";

import DateRange from '~/dev/daterange.vue';
import Index from '~/dev/index.vue';
import Inputs from '~/dev/inputs.vue';
import DateTime from '~/dev/datetime.vue';
import * as components from "~/components/components";

const routes    =   [{
    path: '/',
    component:  Index
}, {
    path: '/inputs',
    component:  Inputs,
    children: [
        {
            path: 'datetime',
            component: DateTime,
        }, {
            path: 'daterange',
            component:  DateRange,
        }
    ]   
}];

/**
 * this definet he router. The configuration
 * that should be used globally by the app.
 */
const router    =   createRouter({
    history: createWebHashHistory(),
    routes
});

const devApp    =   createApp({
    components
});

devApp.use( router );
devApp.mount( '#dev-app' );