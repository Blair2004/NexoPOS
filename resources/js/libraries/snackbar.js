var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import * as rx from 'rx';
var SnackBar = /** @class */ (function () {
    function SnackBar() {
        if (window.snackbarQueue === undefined) {
            window.snackbarQueue = [];
            this.queue = window.snackbarQueue;
        }
    }
    SnackBar.prototype.show = function (message, label, options) {
        var _this = this;
        if (options === void 0) { options = { duration: 3000, type: 'info' }; }
        return rx.Observable.create(function (observer) {
            var _a = _this.__createSnack({ message: message, label: label, type: options.type }), buttonNode = _a.buttonNode, textNode = _a.textNode, snackWrapper = _a.snackWrapper, sampleSnack = _a.sampleSnack;
            buttonNode.addEventListener('click', function (event) {
                observer.onNext(buttonNode);
                observer.onCompleted();
                sampleSnack.remove();
            });
            _this.__startTimer(options.duration, sampleSnack);
        });
    };
    SnackBar.prototype.error = function (message, label, options) {
        if (options === void 0) { options = { duration: 3000, type: 'error' }; }
        return this.show(message, label, __assign(__assign({}, options), { type: 'error' }));
    };
    SnackBar.prototype.success = function (message, label, options) {
        if (options === void 0) { options = { duration: 3000, type: 'success' }; }
        return this.show(message, label, __assign(__assign({}, options), { type: 'success' }));
    };
    SnackBar.prototype.info = function (message, label, options) {
        if (options === void 0) { options = { duration: 3000, type: 'info' }; }
        return this.show(message, label, __assign(__assign({}, options), { type: 'info' }));
    };
    /**
     *
     * @param {number} duration
     * @param {HTMLDivElement} wrapper
     */
    SnackBar.prototype.__startTimer = function (duration, wrapper) {
        var timeout;
        var __startTimeOut = function () {
            if (duration > 0 && duration !== false) {
                timeout = setTimeout(function () {
                    wrapper.remove();
                }, duration);
            }
        };
        wrapper.addEventListener('mouseenter', function () {
            clearTimeout(timeout);
        });
        wrapper.addEventListener('mouseleave', function () {
            __startTimeOut();
        });
        __startTimeOut();
    };
    SnackBar.prototype.__createSnack = function (_a) {
        var message = _a.message, label = _a.label, _b = _a.type, type = _b === void 0 ? 'info' : _b;
        var snackWrapper = document.getElementById('snack-wrapper') || document.createElement('div');
        var sampleSnack = document.createElement('div');
        var textNode = document.createElement('p');
        var buttonsWrapper = document.createElement('div');
        var buttonNode = document.createElement('button');
        var buttonThemeClass = '';
        var snackThemeClass = '';
        switch (type) {
            case 'info':
                buttonThemeClass = 'text-white hover:bg-blue-400 bg-blue-500';
                snackThemeClass = 'bg-gray-900 text-white';
                break;
            case 'error':
                buttonThemeClass = 'text-red-700 hover:bg-white bg-white';
                snackThemeClass = 'bg-red-500 text-white';
                break;
            case 'success':
                buttonThemeClass = 'text-green-700 hover:bg-white bg-white';
                snackThemeClass = 'bg-green-500 text-white';
                break;
        }
        textNode.textContent = message;
        /**
         * if there is not label
         * on the button, it's useless to add it
         */
        if (label) {
            buttonNode.textContent = label;
            buttonNode.setAttribute('class', "px-3 py-2 shadow rounded-lg font-bold " + buttonThemeClass);
            buttonsWrapper.appendChild(buttonNode);
        }
        sampleSnack.appendChild(textNode);
        sampleSnack.appendChild(buttonsWrapper);
        sampleSnack.setAttribute('class', "md:rounded-lg py-2 px-3 md:w-2/5 w-full z-10 md:my-2 shadow-lg flex justify-between items-center " + snackThemeClass);
        snackWrapper.setAttribute('id', 'snack-wrapper');
        snackWrapper.setAttribute('class', 'absolute bottom-0 w-full flex justify-between items-center flex-col');
        snackWrapper.appendChild(sampleSnack);
        document.body.appendChild(snackWrapper);
        return { snackWrapper: snackWrapper, sampleSnack: sampleSnack, buttonsWrapper: buttonsWrapper, buttonNode: buttonNode, textNode: textNode };
    };
    return SnackBar;
}());
export { SnackBar };
//# sourceMappingURL=snackbar.js.map