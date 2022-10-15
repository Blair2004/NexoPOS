declare const window;

import Vue from 'vue';

const nsDatabaseUpdate     =   require( './pages/update/ns-database-update' ).default;

console.log( nsDatabaseUpdate );

(<any>window)[ 'nsUpdate' ]      =   new Vue({
    el: '#main-container',
    components: {
        nsDatabaseUpdate
    }
})