declare const window;

import * as components from "~/components/components";

import { createApp } from 'vue/dist/vue.esm-bundler';
import nsDatabaseUpdate from '~/pages/update/ns-database-update.vue';

window.nsUpdate     =   createApp({
    components: {
        nsDatabaseUpdate
    }
});

/**
 * let's register the component that has
 * a valid name globally
 */
 for( let name in components ) {
    window.nsUpdate.component( name, components[ name ] );
}

window.nsUpdate.mount( '#main-container' );