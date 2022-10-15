import Vue from 'vue';
import VueRouter from 'vue-router';

import { 
    nsButton,
    nsCheckbox,
    nsCrud,
    nsMenu,
    nsSubmenu,
    nsLink,
} from './components/components';

const WelcomeComponent              =   require( './pages/setup/welcome.vue' ).default;
const DatabaseComponent             =   require( './pages/setup/database.vue' ).default;
const SetupConfigurationComponent   =   require( './pages/setup/setup-configuration.vue' ).default;

const routes    =   [
    { path: '/', component: WelcomeComponent },
    { path: '/database', component: DatabaseComponent },
    { path: '/configuration', component: SetupConfigurationComponent },
];

Vue.use( VueRouter );

const nsRouter    =   new VueRouter({ routes });

new Vue({
    router: nsRouter
}).$mount( '#nexopos-setup' );

export { nsRouter };