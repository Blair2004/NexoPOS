import Vue from 'vue';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import * as components from './components/components';
import FormValidation from './libraries/form-validation';
import nsLogin from '@/pages/auth/ns-login.vue';
// const nsLogin       =   () => import( '@/pages/auth/ns-login' );

console.log( Object.keys( nsLogin ) );

const nsRegister    =   require( '@/pages/auth/ns-register' );

const nsState               =   window[ 'nsState' ];
const nsScreen              =   window[ 'nsScreen' ];
const nsExtraComponents     =   window[ 'nsExtraComponents' ];

(<any>window)[ 'nsComponents' ]          =   Object.assign( components, nsExtraComponents );
(<any>window)[ 'authVueComponent' ]      =   new Vue({
    el: '#page-container',
    components: Object.assign({
        nsLogin,
        nsRegister,
    }, components )
});