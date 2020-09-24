import { Popup } from "../../../../../libraries/popup";
import Product from "../../popups/product-quantity.vue";
var ProductQuantityPromise = /** @class */ (function () {
    function ProductQuantityPromise() {
        console.log(Product);
    }
    ProductQuantityPromise.prototype.run = function () {
        return new Promise(function (resolve, reject) {
            console.log('foo');
            var popup = new Popup();
            // popup.open()
        });
    };
    return ProductQuantityPromise;
}());
export { ProductQuantityPromise };
//# sourceMappingURL=product-quantity.js.map