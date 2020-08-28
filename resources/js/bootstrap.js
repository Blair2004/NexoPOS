import * as Lodash from "lodash";
import * as Vue from "vue";
import * as Axios from "axios";
import * as ChartJS from "chart.js";
import VueRouter from "vue-router";
import { EventEmitter, HttpClient, SnackBar, State } from "./libraries/libraries";
import { fromEvent } from "rxjs";

Vue.use( VueRouter );

window._                =   Lodash;
window.CharJS           =   ChartJS;
window.Vue              =   Vue;
window.Axios            =   Axios;
window.VueRouter        =   VueRouter;
window.SnackBar         =   SnackBar;
window.Axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Axios.defaults.headers.common['Authorization'] = `Bearer ${NexoPOS.token}`;

const nsEvent           =   new EventEmitter;
const nsHttpClient      =   new HttpClient;
const nsSnackBar        =   new SnackBar;

/**
 * create a screen class
 * that controls the device sizes
 */
const nsScreen          =   new class {
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

export { Vue, VueRouter, Axios, ChartJS, EventEmitter, SnackBar, nsHttpClient, nsSnackBar, nsEvent, nsState, nsScreen };