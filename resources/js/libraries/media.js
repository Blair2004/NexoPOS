"use strict";
exports.__esModule = true;
exports.Media = void 0;
var popup_1 = require("./popup");
var MediaComponent = require('./../../js/pages/dashboard/ns-media')["default"];
var Media = /** @class */ (function () {
    function Media(field, primarySelector) {
        this.field = field;
        this.primarySelector = primarySelector;
    }
    Media.prototype.open = function () {
        this.popup = new popup_1.Popup({
            popupClass: 'w-4/5 h-4/5-screen shadow-lg bg-white'
        });
        this.popup.open(MediaComponent);
    };
    return Media;
}());
exports.Media = Media;
