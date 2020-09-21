"use strict";
exports.__esModule = true;
exports.Popup = void 0;
var rxjs_1 = require("rxjs");
var Popup = /** @class */ (function () {
    function Popup(config) {
        if (config === void 0) { config = {}; }
        this.config = {
            primarySelector: undefined,
            popupClass: 'shadow-lg h-half w-1/2 bg-white'
        };
        this.container = document.createElement('div');
        this.popupBody = document.createElement('div');
        this.config = Object.assign(this.config, config);
        if (this.config.primarySelector === undefined &&
            document.querySelectorAll('.is-popup').length > 0) {
            var items = document.querySelectorAll('.is-popup').length;
            this.parentWrapper = (document.querySelectorAll('.is-popup')[items - 1]);
        }
        else {
            this.parentWrapper = document.querySelector('body').querySelectorAll('div')[0];
        }
        this.event = new rxjs_1.Subject;
    }
    Popup.prototype.open = function (component) {
        var _this = this;
        var _a, _b, _c;
        console.log(this.parentWrapper);
        this.parentWrapper.style.filter = 'blur(5px)';
        this.container.setAttribute('class', 'absolute top-0 left-0 w-full h-full flex items-center justify-center is-popup');
        /**
         * We need to listen to click even on the container
         * as it might be used to close the popup
         */
        this.container.addEventListener('click', function (event) {
            /**
             * this will emit an even
             * when the overlay is clicked
             */
            _this.event.next({
                event: 'click-overlay',
                value: true
            });
            event.stopPropagation();
        });
        /**
         * We don't want to propagate to the
         * overlay, that closes the popup
         */
        this.popupBody.addEventListener('click', function (event) {
            event.stopImmediatePropagation();
        });
        this.container.style.background = 'rgb(51 51 51 / 20%)';
        this.container.id = 'popup-container-' + document.querySelectorAll('.is-popup').length;
        this.popupBody.setAttribute('class', this.config.popupClass + ' zoom-out-entrance');
        this.popupBody.innerHTML = '<div class="popup-body"></div>';
        this.container.appendChild(this.popupBody);
        document.body.appendChild(this.container);
        /**
         * We'll provide a reference of the
         * wrapper so that the component
         * can manipulate that.
         */
        var componentClass = Vue.extend(component);
        this.instance = new componentClass({
            propsData: {
                popup: this
            }
        });
        /**
         * Let's intanciaate the component
         * and mount it
         */
        this.instance.template = ((_a = component === null || component === void 0 ? void 0 : component.options) === null || _a === void 0 ? void 0 : _a.template) || undefined;
        this.instance.render = component.render || undefined;
        this.instance.methods = ((_b = component === null || component === void 0 ? void 0 : component.options) === null || _b === void 0 ? void 0 : _b.methods) || (component === null || component === void 0 ? void 0 : component.methods);
        this.instance.data = ((_c = component === null || component === void 0 ? void 0 : component.options) === null || _c === void 0 ? void 0 : _c.data) || (component === null || component === void 0 ? void 0 : component.data);
        this.instance.$mount("#" + this.container.id + " .popup-body");
    };
    Popup.prototype.close = function () {
        var _this = this;
        /**
         * Let's start by destorying the
         * Vue component attached to the popup
         */
        this.instance.$destroy();
        /**
         * The Subject we've initialized earlier
         * need to be closed
         */
        this.event.unsubscribe();
        /**
         * For some reason we need to fetch the
         * primary selector once again.
         */
        this.parentWrapper.style.filter = 'blur(0px)';
        this.popupBody.classList.remove('zoom-out-entrance');
        this.popupBody.classList.add('zoom-in-exit');
        setTimeout(function () {
            _this.container.remove();
        }, 300); // as by default the animation is set to 500ms
    };
    return Popup;
}());
exports.Popup = Popup;
