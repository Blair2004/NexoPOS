import * as Lodash from "lodash";
import * as Vue from "vue";
import * as Axios from "axios";
import * as ChartJS from "chart.js";
import VueRouter from "vue-router";
import { EventEmitter, HttpClient, SnackBar, State } from "./libraries/libraries";

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
const nsState           =   new State({
    sidebar: 'visible'
})

nsHttpClient.defineClient( Axios );

export { Vue, VueRouter, Axios, ChartJS, EventEmitter, SnackBar, nsHttpClient, nsSnackBar, nsEvent, nsState };