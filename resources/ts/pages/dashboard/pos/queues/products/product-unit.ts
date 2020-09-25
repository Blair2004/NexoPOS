import { Popup } from "../../../../../libraries/popup";
const nsProductUnitPopup    =   require( './../../popups/ns-pos-units' ).default;

export class ProductUnitPromise {
    constructor( protected product ) {}

    run() {
        return new Promise( ( resolve, reject ) => {
            const popup     =   new Popup;
            const product   =   this.product;
            popup.open( nsProductUnitPopup, { resolve, reject, product });
        });
    }
}