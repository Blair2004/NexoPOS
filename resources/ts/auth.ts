import * as components from './components/components';

import { NsHotPress }       from './libraries/ns-hotpress';
import { createApp } from 'vue/dist/vue.esm-bundler';
import { defineAsyncComponent } from 'vue';

declare let nsExtraComponents;
declare const window;

nsExtraComponents.nsRegister        =   defineAsyncComponent( () => import( './pages/auth/ns-register.vue' ) );
nsExtraComponents.nsLogin           =   defineAsyncComponent( () => import( './pages/auth/ns-login.vue' ) );
nsExtraComponents.nsPasswordLost    =   defineAsyncComponent( () => import( './pages/auth/ns-password-lost.vue' ) );
nsExtraComponents.nsNewPassword     =   defineAsyncComponent( () => import( './pages/auth/ns-new-password.vue' ) );

window.nsHotPress                   =   new NsHotPress;
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