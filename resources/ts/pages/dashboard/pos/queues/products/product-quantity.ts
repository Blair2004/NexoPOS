import { nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
import { Popup } from '@/libraries/popup';
import { default as nsPosQuantityPopup } from '@/popups/ns-pos-quantity-popup.vue';

declare const POS;

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
            const options   =   POS.options.getValue();

            /**
             * that will only bypass quantity selection when we're 
             * processing the addQueue. Otherwhise it will open the POPUP.
             */
            if ( options.ns_pos_show_quantity !== false || ! POS.processingAddQueue ) {
                popup.open( nsPosQuantityPopup, { resolve, reject, product, data });
            } else {
                const quantity      =       1;

                /**
                 * The stock should be handled differently
                 * according to whether the stock management
                 * is enabled or not.
                 */
                if ( product.$original().stock_management === 'enabled' && product.$original().type === 'materialized' ) {

                    /**
                     * If the stock management is enabled,
                     * we'll pull updated stock from the server.
                     * When a product is added product.id has the real product id
                     * when a product is already on the cart product.id is not set but
                     * product.product_id is defined
                     */
                    const holdQuantity  =   POS.getStockUsage( product.$original().id, data.unit_quantity_id ) - ( product.quantity || 0 );

                    /**
                     * This checks if there is enough
                     * quantity for product that has stock 
                     * management enabled
                     */
                    if ( 
                        quantity > (
                            parseFloat( data.$quantities().quantity ) -
                            /**
                             * We'll make sure to ignore the product quantity 
                             * already added to the cart by substracting the 
                             * provided quantity.
                             */
                            ( holdQuantity )
                        )
                    ) {
                        return nsSnackBar.error( __( 'Unable to add the product, there is not enough stock. Remaining %s' ).replace( '%s', ( data.$quantities().quantity - holdQuantity ).toString() ) )
                            .subscribe();
                    }
                }

                resolve({ quantity });
            }            
        });
    }
}
