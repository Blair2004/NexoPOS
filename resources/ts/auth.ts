import { timeStamp } from 'console';
import { forkJoin } from 'rxjs';
import Vue from 'vue';
import { nsHttpClient, nsSnackBar } from './bootstrap';
import * as components from './components/components';
import FormValidation from './libraries/form-validation';

const nsLogin       =   require( '@/pages/auth/ns-login' ).default;
const nsRegister    =   require( '@/pages/auth/ns-register' ).default;

declare const nsState;
declare const nsScreen;
declare const nsExtraComponents;

(<any>window).nsComponents          =   { ...components, ...nsExtraComponents };
(<any>window).authVueComponent      =   new Vue({
    el: '#page-container',
    components: {
        nsLogin,
        nsRegister,
        ...components
    }
});