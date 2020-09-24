import { Popup } from './popup';
var MediaComponent = require('./../../js/pages/dashboard/ns-media').default;
var Media = /** @class */ (function () {
    function Media(field, primarySelector) {
        this.field = field;
        this.primarySelector = primarySelector;
    }
    Media.prototype.open = function () {
        this.popup = new Popup({
            popupClass: 'w-4/5 h-4/5-screen shadow-lg bg-white'
        });
        this.popup.open(MediaComponent);
    };
    return Media;
}());
export { Media };
//# sourceMappingURL=media.js.map