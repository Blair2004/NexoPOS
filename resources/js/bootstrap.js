import * as Lodash from "lodash";
import * as Vue from "vue";
import * as Axios from "axios";
import * as ChartJS from "chart.js";
import VueRouter from "vue-router";
import { EventEmitter, HttpClient, SnackBar } from "./libraries/libraries";

Vue.use( VueRouter );

window._                =   Lodash;
window.CharJS           =   ChartJS;
window.Vue              =   Vue;
window.Axios            =   Axios;
window.VueRouter        =   VueRouter;
window.SnackBar         =   SnackBar;
window.Axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const nsEvent           =   new EventEmitter;
const nsHttpClient      =   new HttpClient;
const nsSnackBar        =   new SnackBar;

nsHttpClient.defineClient( Axios );

export { Vue, VueRouter, Axios, ChartJS, EventEmitter, SnackBar, nsHttpClient, nsSnackBar, nsEvent };