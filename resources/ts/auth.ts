import { nsHooks, nsSnackBar } from '~/bootstrap';
import { defineAsyncComponent } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import { createApp } from 'vue/dist/vue.esm-bundler';
import * as components from './components/components';
import FormValidation from './libraries/form-validation';

declare let nsExtraComponents;
declare const window;

nsExtraComponents.nsRegister        =   defineAsyncComponent( () => import( './pages/auth/ns-register.vue' ) );
nsExtraComponents.nsLogin           =   defineAsyncComponent( () => import( './pages/auth/ns-login.vue' ) );
nsExtraComponents.nsPasswordLost    =   defineAsyncComponent( () => import( './pages/auth/ns-password-lost.vue' ) );
nsExtraComponents.nsNewPassword     =   defineAsyncComponent( () => import( './pages/auth/ns-new-password.vue' ) );

// const nsState               =   window[ 'nsState' ];
// const nsScreen              =   window[ 'nsScreen' ];

window.nsHttpClient                 =   nsHttpClient;
window.authVueComponent             =   createApp({
    components: {
        ...nsExtraComponents,
        ...components
    }
});

/**
 * Global component registration.
 * Those components are widely used on the app.
 */
for( let name in components ) {
    window.authVueComponent.component( name, components[ name ] );
}

window.authVueComponent.mount( '#page-container' );