declare const window;

import { createApp } from 'vue/dist/vue.esm-bundler';
import nsDatabaseUpdate from '~/pages/update/ns-database-update.vue';

console.log( nsDatabaseUpdate );

window.nsUpdate     =   createApp({
    components: {
        nsDatabaseUpdate
    }
});

window.nsUpdate.mount( '#main-container' );