const lodash        =   require( 'lodash' );
const chart         =   require( 'chart.js' );
const Vue           =   require( 'vue' );
const axios         =   require( 'axios' );
const VueRouter     =   require( 'vue-router' ).default;

Vue.use( VueRouter );

window._                =   lodash;
window.charjs           =   chart;
window.Vue              =   Vue;
window.axios            =   axios;
window.VueRouter        =   VueRouter;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const { EventEmitter, HttpClient }  =   require( './libraries/libraries' );

const nsEvent           =   new EventEmitter;
const nsHttpClient      =   new HttpClient;
nsHttpClient.defineClient( axios );

module.exports._                =   _;
module.exports.Vue              =   Vue;
module.exports.VueRouter        =   VueRouter;
module.exports.axios            =   axios;
module.exports.chartjs          =   chart;
module.exports.nsEvent          =   nsEvent;
module.exports.EventEmitter     =   EventEmitter;
module.exports.nsHttpClient     =   nsHttpClient;
