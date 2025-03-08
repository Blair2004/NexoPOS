import * as components from "~/components/components";

import { createApp, defineComponent } from "vue";
import { createRouter, createWebHashHistory } from "vue-router";

import Date from '~/dev/date.vue';
import DateRange from '~/dev/daterange.vue';
import DateTime from '~/dev/datetime.vue';
import Index from '~/dev/index.vue';
import InlineMultiselect from '~/dev/inline-multiselect.vue';
import Inputs from '~/dev/inputs.vue';
import Multiselect from '~/dev/multiselect.vue';
import Upload from '~/dev/upload.vue';
import Ckeditor from '~/dev/ckeditor.vue';
import Input from '~/dev/input.vue';

const routes    =   [{
    path: '/',
    component:  Index
}, {
    path: '/inputs',
    component:  Inputs,
    children: [
        {
            path: 'date',
            component: Date,
        },{
            path: 'input',
            component: Input,
        }, {
            path: 'daterange',
            component:  DateRange,
        }, {
            path: 'datetime',
            component: DateTime,
        }, {
            path: 'inline-multiselect',
            component: InlineMultiselect,
        },  {
            path: 'multiselect',
            component: Multiselect,
        }, {
            path: 'upload',
            component: Upload,
        }, {
            path: 'ckeditor',
            component: Ckeditor,
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

const devApp    =   createApp({});

for( const component in components ) {
    devApp.component( component, components[ component ] );
}

devApp.use( router );
devApp.mount( '#dev-app' );