/**
 * will bootstrap time
 */
import Vue from 'vue';
import './shared/time';
var NsPos = require('./pages/dashboard/pos/ns-pos.vue').default;
var NsPosCart = require('./pages/dashboard/pos/ns-pos-cart.vue').default;
var NsPosGrid = require('./pages/dashboard/pos/ns-pos-grid.vue').default;
new Vue({
    el: '#pos-app',
    components: {
        NsPos: NsPos,
        NsPosCart: NsPosCart,
        NsPosGrid: NsPosGrid
    }
});
//# sourceMappingURL=pos.js.map