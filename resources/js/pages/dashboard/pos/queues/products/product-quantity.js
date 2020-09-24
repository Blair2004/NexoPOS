import { Popup } from "../../../../../libraries/popup";
// import { ProductQuantity } from "../../popups/product-quantity.vue";
var ProductQuantityPromise = /** @class */ (function () {
    function ProductQuantityPromise() {
    }
    ProductQuantityPromise.prototype.run = function () {
        return new Promise(function (resolve, rejeect) {
            console.log('foo');
            var popup = new Popup();
            // popup.open()
        });
    };
    return ProductQuantityPromise;
}());
export { ProductQuantityPromise };
//# sourceMappingURL=product-quantity.js.map