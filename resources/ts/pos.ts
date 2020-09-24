/**
 * will bootstrap time
 */
import Vue from 'vue';

import './shared/time';

import { 
    nsMenu,
    nsSubmenu,
    nsButton,
    nsLink,
    nsInput,
    nsSelect,
    nsCheckbox,
    nsCrud,
    nsTableRow,
    nsSpinner,
    nsCrudForm,
    nsTextarea,
    nsField,
    nsMultiselect,
    nsSwitch,
    nsDate,
    nsMediaInput,
} from './components/components';

import { nsCurrency } from './filters/declarations';

const NsPos         =   require( './pages/dashboard/pos/ns-pos.vue' ).default;
const NsPosCart     =   require( './pages/dashboard/pos/ns-pos-cart.vue' ).default;
const NsPosGrid     =   require( './pages/dashboard/pos/ns-pos-grid.vue' ).default;

new Vue({
    el: '#pos-app',
    components: {
        NsPos,
        NsPosCart,
        NsPosGrid
    }
})