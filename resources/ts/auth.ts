import Vue from 'vue';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import * as components from './components/components';
import FormValidation from './libraries/form-validation';

const nsRegister            =   require( '@/pages/auth/ns-register' ).default;
const nsLogin               =   require( '@/pages/auth/ns-login' ).default;
const nsPasswordLost        =   require( '@/pages/auth/ns-password-lost' ).default;
const nsNewPassword         =   require( '@/pages/auth/ns-new-password' ).default;

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