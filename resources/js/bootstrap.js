import * as Lodash from "lodash";
import * as Axios from "axios";
import * as ChartJS from "chart.js";
import VueRouter from "vue-router";
import { EventEmitter, HttpClient, SnackBar, State } from "./libraries/libraries";
import { fromEvent } from "rxjs";
import * as RxJS from 'rxjs';
import * as moment from 'moment';
import { Popup } from "./libraries/popup";
import { Media } from "./libraries/media";
import Vue from "vue";
Vue.use(VueRouter);
window._ = Lodash;
window.CharJS = ChartJS;
window.Vue = Vue;
window.moment = moment;
window.Axios = Axios;
window.VueRouter = VueRouter;
window.SnackBar = SnackBar;
window.Axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Axios.defaults.headers.common['Authorization'] = "Bearer " + ns.authentication.token;
var nsEvent = new EventEmitter;
var nsHttpClient = new HttpClient;
var nsSnackBar = new SnackBar;
/**
 * create a screen class
 * that controls the device sizes
 */
var nsScreen = new /** @class */ (function () {
    function class_1() {
        var _this = this;
        this.breakpoint = '';
        this.detectScreenSizes();
        fromEvent(window, 'resize')
            .subscribe(function (v) { return _this.detectScreenSizes(); });
    }
    class_1.prototype.detectScreenSizes = function () {
        switch (true) {
            case (window.outerWidth > 0) && (window.outerWidth <= 480):
                this.breakpoint = 'xs';
                break;
            case (window.outerWidth > 480) && (window.outerWidth <= 640):
                this.breakpoint = 'sm';
                break;
            case (window.outerWidth > 640) && (window.outerWidth <= 1024):
                this.breakpoint = 'md';
                break;
            case (window.outerWidth > 1024) && (window.outerWidth <= 1280):
                this.breakpoint = 'lg';
                break;
            case (window.outerWidth > 1280):
                this.breakpoint = 'xl';
                break;
        }
    };
    return class_1;
}());
var nsState = new State({
    sidebar: ['xs', 'sm', 'md'].includes(nsScreen.breakpoint) ? 'hidden' : 'visible'
});
nsHttpClient.defineClient(Axios);
window.nsEvent = nsEvent;
window.nsHttpClient = nsHttpClient;
window.nsSnackBar = nsSnackBar;
window.nsState = nsState;
window.nsScreen = nsScreen;
window.ChartJS = ChartJS;
window.EventEmitter = EventEmitter;
window.Popup = Popup;
window.RxJS = RxJS;
window.Media = Media;
export { Vue, VueRouter, Axios, ChartJS, EventEmitter, SnackBar, nsHttpClient, nsSnackBar, nsEvent, nsState, nsScreen };
//# sourceMappingURL=bootstrap.js.map