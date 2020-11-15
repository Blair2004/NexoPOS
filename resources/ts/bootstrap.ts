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
import { nsCurrency, nsAbbreviate } from "./filters/declarations";
import CrudHandler from "./libraries/crud-handler";

declare const window;
declare const ns;

window._                =   Lodash;
window.CharJS           =   ChartJS;
window.Vue              =   Vue;
window.moment           =   moment;
window.Axios            =   Axios;
window.VueRouter        =   VueRouter;
window.SnackBar         =   SnackBar;
window.Axios.defaults.headers.common['x-requested-with']    =   'XMLHttpRequest';
window.Axios.defaults.withCredentials                       =   true;

const nsEvent           =   new EventEmitter;
const nsHttpClient      =   new HttpClient;
const nsSnackBar        =   new SnackBar;
const nsUrl             =   new Url;
const nsCrudHandler     =   new CrudHandler;

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

window.nsEvent          =   nsEvent;
window.nsHttpClient     =   nsHttpClient;
window.nsSnackBar       =   nsSnackBar;
window.nsCurrency       =   nsCurrency;
window.nsAbbreviate     =   nsAbbreviate;
window.nsState          =   nsState;
window.nsUrl            =   nsUrl;
window.nsScreen         =   nsScreen;
window.ChartJS          =   ChartJS;
window.EventEmitter     =   EventEmitter;
window.Popup            =   Popup;
window.RxJS             =   RxJS;
window.FormValidation   =   FormValidation;
window.nsCrudHandler    =   nsCrudHandler;

export { nsHttpClient, nsSnackBar, nsEvent, nsState, nsScreen, nsUrl };