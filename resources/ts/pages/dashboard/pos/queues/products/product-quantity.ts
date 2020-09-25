import { Popup } from "../../../../../libraries/popup";

const nsPosQuantityPopup    =   require( '../../popups/ns-pos-quantity-popup' ).default;

export class ProductQuantityPromise {
    constructor( 
        protected product 
    ) {}

    run() {
        return new Promise( ( resolve, reject ) => {
            const popup     =   new Popup();
            const product   =   this.product;
            
            popup.open( nsPosQuantityPopup, { resolve, reject, product });
        });
    }
}