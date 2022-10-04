import * as Lodash from "lodash";
import * as Axios from "axios";
import * as ChartJS from "chart.js";
import VueRouter from "vue-router";
import { EventEmitter, HttpClient, SnackBar, State } from "./libraries/libraries";
import { fromEvent } from "rxjs";
import * as RxJS from 'rxjs';
import * as moment from 'moment';
import { Popup } from "./libraries/popup";
import Vue from "vue";
import FormValidation from "./libraries/form-validation";
import Url from "./libraries/url";
import { nsCurrency, nsAbbreviate, nsRawCurrency, nsTruncate } from "./filters/declarations";
import CrudHandler from "./libraries/crud-handler";
import { createHooks } from '@wordpress/hooks';
import { __, __m } from "./libraries/lang";
import popupResolver from "./libraries/popup-resolver";
import popupCloser from "./libraries/popup-closer";
import Echo from "laravel-echo";
const Pusher    =   require('pusher-js');


declare global {
    interface Window {
        _: Lodash,
        ChartJS: any,
        Vue: any,
        moment: moment.Moment,
        Axios: any,
        VueRouter: VueRouter,
        nsHooks: any,
        SnackBar: SnackBar,
        __: any,
        __m: any,
        popupResolver: any,
        popupCloser: any,
        Pusher:any,
        Echo: any
    }
};

declare const ns;

window._                =   Lodash;
window.ChartJS          =   ChartJS;
window.Pusher           =   Pusher;
window.Vue              =   Vue;
window.moment           =   <any>moment;
window.Axios            =   Axios;
window.__               =   __;
window.__m              =   __m;
window.VueRouter        =   <any>VueRouter;
window.SnackBar         =   <any>SnackBar;
window.nsHooks          =   createHooks();
window.popupResolver    =   popupResolver,
window.popupCloser      =   popupCloser,
window.Axios.defaults.headers.common['x-requested-with']    =   'XMLHttpRequest';
window.Axios.defaults.withCredentials                       =   true;

if ( ns.websocket.enabled ) {
    window.Echo             =   new Echo({
        broadcaster: 'pusher',
        key: ns.websocket.key,
        wsHost: ns.websocket.host,
        wsPort: ns.websocket.port,
        wssPort: ns.websocket.port,
        namespace: '',
        forceTLS: ns.websocket.secured,
        disableStats: true,
        encrypted: ns.websocket.secured,
        enabledTransports: ns.websocket.secured ? [ 'ws', 'wss' ] : [ 'ws' ],
        disabledTransports: ns.websocket.secured ? ['sockjs', 'xhr_polling', 'xhr_streaming'] : []
    })
}

const nsEvent           =   new EventEmitter;
const nsHttpClient      =   new HttpClient;
const nsSnackBar        =   new SnackBar;
const nsUrl             =   new Url;
const nsCrudHandler     =   new CrudHandler;
const nsHooks           =   window.nsHooks;

/**
 * create a screen class
 * that controls the device sizes
 */
const nsScreen          =   new class {
    breakpoint: string;
    
    constructor() {
        this.breakpoint     =   '';
        
        this.detectScreenSizes();

        fromEvent( window, 'resize' )
            .subscribe( v => this.detectScreenSizes() );
    }

    detectScreenSizes() {
        switch( true ) {
            case ( window.outerWidth > 0 ) && ( window.outerWidth <= 480 ) :
                this.breakpoint     =   'xs';
            break;
            case ( window.outerWidth > 480 ) && ( window.outerWidth <= 640 ) :
                this.breakpoint     =   'sm';
            break;
            case ( window.outerWidth > 640 ) && ( window.outerWidth <= 1024 ) :
                this.breakpoint     =   'md';
            break;
            case ( window.outerWidth > 1024 ) && ( window.outerWidth <= 1280 ):
                this.breakpoint     =   'lg';
            break;
            case ( window.outerWidth > 1280 ) :
                this.breakpoint     =   'xl';
            break;
        }
    }
}

const nsState           =   new State({
    sidebar: [ 'xs', 'sm', 'md' ].includes( nsScreen.breakpoint ) ? 'hidden' : 'visible'
});

nsHttpClient.defineClient( Axios );

( window as any ).nsEvent            =   nsEvent;
( window as any ).nsHttpClient       =   nsHttpClient;
( window as any ).nsSnackBar         =   nsSnackBar;
( window as any ).nsCurrency         =   nsCurrency;
( window as any ).nsTruncate         =   nsTruncate;
( window as any ).nsRawCurrency      =   nsRawCurrency;
( window as any ).nsAbbreviate       =   nsAbbreviate;
( window as any ).nsState            =   nsState;
( window as any ).nsUrl              =   nsUrl;
( window as any ).nsScreen           =   nsScreen;
( window as any ).ChartJS            =   ChartJS;
( window as any ).EventEmitter       =   EventEmitter;
( window as any ).Popup              =   Popup;
( window as any ).RxJS               =   RxJS;
( window as any ).FormValidation     =   FormValidation;
( window as any ).nsCrudHandler      =   nsCrudHandler;

export { nsHttpClient, nsSnackBar, nsEvent, nsState, nsScreen, nsUrl, nsHooks };