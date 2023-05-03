import { Popup } from "~/libraries/popup";
import nsProductUnitPopup from '~/popups/ns-pos-units.vue';

export class ProductUnitPromise {
    constructor( protected product ) {}

    run() {
        return new Promise( ( resolve, reject ) => {
            const product   =   this.product;
            Popup.show( nsProductUnitPopup, { resolve, reject, product });
        });
    }
}