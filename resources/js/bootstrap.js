window._                = require('lodash');
window.axios            = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.charjs           =   require( 'chart.js' );
window.Vue              =   require( 'vue' );

require( './components/index' );
const { EventEmitter }  =   require( './libraries/index' );

window.MenuEvent        =   new EventEmitter;