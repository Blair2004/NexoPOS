import { Popup } from "../../../../../libraries/popup";

import Product from "../../popups/product-quantity.vue";

export class ProductQuantityPromise {
    constructor() {
        console.log( Product );
    }
    
    run() {
        return new Promise( ( resolve, reject) => {
            console.log( 'foo' );
            const popup     =   new Popup();
            // popup.open()
        });
    }
}