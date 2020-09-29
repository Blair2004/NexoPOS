import { Popup } from "../../../../../libraries/popup";

const nsPosQuantityPopup    =   require( '../../popups/ns-pos-quantity-popup' ).default;

export class ProductQuantityPromise {
    constructor( 
        protected product 
    ) {}

    run( data ) {
        return new Promise( ( resolve, reject ) => {
            const popup     =   new Popup({
                popupClass: 'shadow-lg h-1/2-screen w-3/4 md:w-1/2 lg:w-2/5 xl:w-1/4 bg-white'
            });
            
            const product   =   this.product;
            
            popup.open( nsPosQuantityPopup, { resolve, reject, product, data });
        });
    }
}