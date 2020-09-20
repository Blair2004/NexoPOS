"use strict";
exports.__esModule = true;
exports.Popup = void 0;
var Popup = /** @class */ (function () {
    function Popup(config) {
        this.config = {
            primarySelector: '',
            popupClass: 'shadow-lg h-half w-1/2 bg-white'
        };
        this.container = document.createElement('div');
        this.popupBody = document.createElement('div');
        this.config = Object.assign(this.config, config);
        this.primarySelector = document.querySelector(this.config.primarySelector);
    }
    Popup.prototype.show = function (component) {
        this.primarySelector.style.filter = 'blur(5px)';
        this.container.setAttribute('class', 'absolute top-0 left-0 w-full h-full flex items-center justify-center');
        this.container.id = 'popup-container';
        this.popupBody.setAttribute('class', this.config.popupClass);
        this.popupBody.innerHTML = '<component v-bind:is="component"></component>';
        this.container.appendChild(this.popupBody);
        document.body.appendChild(this.container);
        /**
         * We'll provide a reference of the
         * wrapper so that the component
         * can manipulate that.
         */
        var componentClass = Vue.extend(component);
        var instance = new componentClass();
        instance.$popup = this;
        instance.template = component.options.template;
        instance.methods = component.options.methods;
        instance.data = component.options.data;
        instance.$mount();
        this.vue = new Vue({
            el: "#" + this.container.id,
            data: {
                component: instance
            }
        });
    };
    Popup.prototype.close = function () {
        this.primarySelector.style.filter = 'blur(0px)';
        var element = document.querySelector('#popup-wrapper');
        // make some animation
        element.remove();
    };
    return Popup;
}());
exports.Popup = Popup;
