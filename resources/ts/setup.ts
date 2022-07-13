import Vue, { createApp, defineAsyncComponent } from 'vue';
import VueRouter from 'vue-router';
import * as components from './components/components';

const WelcomeComponent              =   defineAsyncComponent( () => import( './pages/setup/welcome.vue' ) );
const DatabaseComponent             =   defineAsyncComponent( () => import( './pages/setup/database.vue' ) );
const SetupConfigurationComponent   =   defineAsyncComponent( () => import( './pages/setup/setup-configuration.vue' ) );

const routes    =   [
    { path: '/', component: WelcomeComponent },
    { path: '/database', component: DatabaseComponent },
    { path: '/configuration', component: SetupConfigurationComponent },
];

const nsRouter      =   VueRouter.createRouter({ routes, history: VueRouter.createWebHashHistory() });
const nsRouterApp   =   createApp({});

nsRouterApp.use( nsRouter );

/**
 * Global component registration.
 * Those components are widely used on the app.
 */
 for( let name in components ) {
    nsRouterApp.component( name, components[ name ] );
}

nsRouterApp.mount( '#nexopos-setup' );

export { nsRouter };