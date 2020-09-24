import { Popup } from "../../../../../libraries/popup";

// import { ProductQuantity } from "../../popups/product-quantity.vue";

export class ProductQuantityPromise {
    run() {
        return new Promise( ( resolve, rejeect) => {
            console.log( 'foo' );
            const popup     =   new Popup();
            // popup.open()
        });
    }
}