/**
 * will bootstrap time
 */
import Vue from 'vue';
import './shared/time';
import './components/components';
import * as baseComponents from './components/components';

const NsPos                     =   require( './pages/dashboard/pos/ns-pos.vue' ).default;
const NsPosCart                 =   require( './pages/dashboard/pos/ns-pos-cart.vue' ).default;
const NsPosGrid                 =   require( './pages/dashboard/pos/ns-pos-grid.vue' ).default;
( window as any ).nsComponents  =   { ...baseComponents };

new Vue({
    el: '#pos-app',
    components: {
        NsPos,
        NsPosCart,
        NsPosGrid,
        ...( window as any ).nsComponents,
    }
})