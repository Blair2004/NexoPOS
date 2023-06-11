import Vue from 'vue';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import * as components from './components/components';
import FormValidation from './libraries/form-validation';

const nsRegister            =   () => import( './pages/auth/ns-register.vue' );
const nsLogin               =   () => import( './pages/auth/ns-login.vue' );
const nsPasswordLost        =   () => import( './pages/auth/ns-password-lost.vue' );
const nsNewPassword         =   () => import( './pages/auth/ns-new-password.vue' );

const nsState               =   window[ 'nsState' ];
const nsScreen              =   window[ 'nsScreen' ];
const nsExtraComponents     =   window[ 'nsExtraComponents' ];

(<any>window)[ 'nsComponents' ]          =   Object.assign( components, nsExtraComponents, {
    nsRegister,
    nsLogin,
    nsPasswordLost,
    nsNewPassword
});

(<any>window)[ 'authVueComponent' ]      =   new Vue({
    el: '#page-container',
    components: {
        nsLogin,
        nsRegister,
        nsPasswordLost,
        nsNewPassword,
        ...nsExtraComponents,
        ...components
    }
});