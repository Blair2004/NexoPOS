import * as Lodash from "lodash";
import EchoClass from "laravel-echo";
import Pusher from 'pusher-js';
import axios from "axios";
import * as ChartJS from "chart.js";
import { fromEvent } from "rxjs";
import * as RxJS from 'rxjs';
import { default as moment } from 'moment';
import { createApp } from "vue/dist/vue.esm-bundler";
import { Popup } from "~/libraries/popup";
import { EventEmitter, HttpClient, SnackBar, State, FloatingNotice } from "./libraries/libraries";
import FormValidation from "./libraries/form-validation";
import Url from "./libraries/url";
import countdown from "./libraries/countdown";
import CrudHandler from "./libraries/crud-handler";
import { createHooks } from '@wordpress/hooks';
import { __, __m } from "./libraries/lang";
import { insertAfterKey, insertBeforeKey } from "./libraries/object";
import popupResolver from "./libraries/popup-resolver";
import popupCloser from "./libraries/popup-closer";
import { timespan } from "./libraries/timespan";
import { defineAsyncComponent, defineComponent, markRaw, shallowRef } from "vue";
import { nsCurrency, nsRawCurrency } from "./filters/currency";
import { nsAbbreviate } from "./filters/abbreviate";
import { nsTruncate } from "./filters/truncate";
import Tax from "./libraries/tax";
import Print from "./libraries/print";


declare global {
    interface Window {
        _: Lodash,
        ChartJS: any,
        createApp: any,
        moment: moment.Moment,
        Axios: any,
        nsHooks: any,
        SnackBar: SnackBar,
        FloatingNotice: FloatingNotice,
        __: any,
        __m: any,
        popupResolver: any,
        popupCloser: any,
        Pusher:any,
        EchoClass: any,
        timespan: any,
        countdown: any
    }
};

declare const ns;

window._                =   Lodash;
window.ChartJS          =   ChartJS;
window.Pusher           =   Pusher;
window.createApp        =   createApp;
window.moment           =   <any>moment;
window.Axios            =   axios;
window.__               =   __;
window.__m              =   __m;
window.SnackBar         =   <any>SnackBar;
window.FloatingNotice   =   <any>FloatingNotice;
window.nsHooks          =   createHooks();
window.popupResolver    =   popupResolver,
window.popupCloser      =   popupCloser,
window.countdown        =   countdown;
window.timespan         =   timespan;

window.Axios.defaults.headers.common['x-requested-with']    =   'XMLHttpRequest';
window.Axios.defaults.withCredentials                       =   true;
window.EchoClass        =   EchoClass;

const nsEvent           =   new EventEmitter;
const nsHttpClient      =   new HttpClient;
const nsSnackBar        =   new SnackBar;
const nsNotice          =   new FloatingNotice;
const nsUrl             =   new Url;
const nsCrudHandler     =   new CrudHandler;
const nsHooks           =   window.nsHooks;

/**
 * create a screen class
 * that controls the device sizes.
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

nsHttpClient.defineClient( axios );

( window as any ).nsEvent               =   nsEvent;
( window as any ).nsHttpClient          =   nsHttpClient;
( window as any ).nsSnackBar            =   nsSnackBar;
( window as any ).nsNotice              =   nsNotice;
( window as any ).nsState               =   nsState;
( window as any ).nsUrl                 =   nsUrl;
( window as any ).nsScreen              =   nsScreen;
( window as any ).ChartJS               =   ChartJS;
( window as any ).EventEmitter          =   EventEmitter;
( window as any ).Popup                 =   Popup;
( window as any ).RxJS                  =   RxJS;
( window as any ).FormValidation        =   FormValidation;
( window as any ).nsCrudHandler         =   nsCrudHandler;
( window as any ).defineComponent       =   defineComponent;
( window as any ).defineAsyncComponent  =   defineAsyncComponent;
( window as any ).markRaw               =   markRaw;
( window as any ).shallowRef            =   shallowRef;
( window as any ).createApp             =   createApp;
( window as any ).ns.insertAfterKey     =   insertAfterKey;
( window as any ).ns.insertBeforeKey    =   insertBeforeKey;
( window as any ).nsCurrency            =   nsCurrency;
( window as any ).nsAbbreviate          =   nsAbbreviate;
( window as any ).nsRawCurrency         =   nsRawCurrency;
( window as any ).nsTruncate            =   nsTruncate;
( window as any ).nsTax                 =   Tax;
( window as any ).PrintService          =   Print;

export { nsSnackBar, nsNotice, nsHttpClient, nsEvent, nsState, nsScreen, nsUrl, nsHooks };