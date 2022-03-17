<template>
    <div class="ns-box shadow min-h-2/5-screen w-3/4-screen md:w-3/5-screen lg:w-2/5-screen xl:w-2/5-screen relative">
        <div id="loading-overlay" v-if="isLoading" style="background:rgb(202 202 202 / 49%)" class="flex w-full h-full absolute top-O left-0 items-center justify-center">
            <ns-spinner></ns-spinner>
        </div>
        <div class="flex-shrink-0 flex justify-between items-center p-2 border-b ns-box-header">
            <div>
                <h1 class="text-xl font-bold text-primary text-center">{{ __( 'Define Quantity' ) }}</h1>
            </div>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div id="screen" class="h-16 border-b primary ns-box-body flex items-center justify-center">
            <h1 class="font-bold text-3xl">{{ finalValue }}</h1>
        </div>
        <div id="numpad" class="ns-box-body grid grid-flow-row grid-cols-3 grid-rows-3">
            <div 
                @click="inputValue( key )"
                :key="index" 
                v-for="(key,index) of keys" 
                class="ns-numpad-key info text-xl font-bold border h-24 flex items-center justify-center cursor-pointer">
                <span v-if="key.value !== undefined">{{ key.value }}</span>
                <i v-if="key.icon" class="las" :class="key.icon"></i>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
export default {
    data() {
        return {
            finalValue: 1,
            virtualStock: null,
            allSelected: true,
            isLoading: false,
            keys: [
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 }, { identifier: 'next', icon: 'la-share' }],
            ]
        }
    },
    mounted() {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                /**
                 * as this runs under a Promise
                 * we need to make sure that
                 * it resolve false using the "resolve" function
                 * provided as $popupParams.
                 * Here we resolve "false" as the user has broken the Promise
                 */
                this.$popupParams.reject( false );

                /**
                 * we can safely close the popup.
                 */
                this.$popup.close();
            }
        });

        /**
         * if the quantity is defined, then probably
         * we're already trying to edit an existing product
         */
        if ( this.$popupParams.product.quantity ) {
            this.finalValue     =   this.$popupParams.product.quantity;
        }

        document.addEventListener( 'keyup', this.handleKeyPress );
    },
    destroyed() {
        document.removeEventListener( 'keypress', this.handleKeyPress );
    },
    methods: {
        __,

        closePopup() {
            this.$popupParams.reject( false );
            this.$popup.close();
        },

        handleKeyPress( event ) {
            if ( event.keyCode === 13 ) {
                this.inputValue({ identifier : 'next' });
            }
        },
        
        inputValue( key ) {
            if ( key.identifier === 'next' ) {
                /**
                 * resolve is provided only on the addProductQueue
                 */
                const { product, data }         =   this.$popupParams;
                const quantity                  =   parseFloat( this.finalValue );

                if ( quantity === 0 ) {
                    return nsSnackBar.error( __( 'Please provide a quantity' ) )
                        .subscribe();
                }

                /**
                 * The stock should be handled differently
                 * according to wether the stock management
                 * is enabled or not.
                 */
                if ( product.$original().stock_management === 'enabled' ) {

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
                        return nsSnackBar.error( __( 'Unable to add the product, there is not enough stock. Remaining %s' ).replace( '%s', ( data.$quantities().quantity - holdQuantity ) ) )
                            .subscribe();
                    }
                }

                this.resolve({ quantity });

            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    this.finalValue     =   0;
                    this.allSelected    =   false;
                } else {
                    this.finalValue     =   this.finalValue.toString();
                    this.finalValue     =   this.finalValue.substr(0, this.finalValue.length - 1 ) || 0;
                }
            } else {
                if ( this.allSelected ) {
                    this.finalValue     =   key.value;
                    this.finalValue     =   parseFloat( this.finalValue );
                    this.allSelected    =   false;
                } else {
                    this.finalValue     +=  '' + key.value;
                    this.finalValue     =   parseFloat( this.finalValue );
                }
            } 
        },

        resolve( params ) {
            this.$popupParams.resolve( params );
            this.$popup.close();
        }
    }
}
</script>