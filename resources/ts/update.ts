declare const window;

import Vue, { createApp } from 'vue';
import nsDatabaseUpdate from '~/pages/update/ns-database-update.vue';

console.log( nsDatabaseUpdate );

window.nsUpdate     =   createApp({
    components: {
        nsDatabaseUpdate
    }
});

window.nsUpdate.mount( '#main-container' );