import * as components from './components/components';

import { createRouter, createWebHashHistory } from 'vue-router';

import { createApp } from 'vue';

const WelcomeComponent              =   () => import( './pages/setup/welcome.vue' );
const DatabaseComponent             =   () => import( './pages/setup/database.vue' );
const SetupConfigurationComponent   =   () => import( './pages/setup/setup-configuration.vue' );

const routes    =   [
    { path: '/', component: WelcomeComponent },
    { path: '/database', component: DatabaseComponent },
    { path: '/configuration', component: SetupConfigurationComponent },
];

const nsRouter      =   createRouter({ routes, history: createWebHashHistory() });

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

(window as any).nsRouter     =   nsRouter;