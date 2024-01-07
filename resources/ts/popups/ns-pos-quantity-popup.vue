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
        <div id="screen" class="h-24 primary ns-box-body flex items-center justify-center">
            <h1 class="font-bold text-3xl">{{ finalValue }}</h1>
        </div>
        <ns-numpad v-if="options.ns_pos_numpad === 'default'" :floating="options.ns_pos_allow_decimal_quantities" @changed="updateQuantity( $event )" @next="defineQuantity( $event )" :value="finalValue"></ns-numpad>
        <ns-numpad-plus v-if="options.ns_pos_numpad === 'advanced'" @changed="updateQuantity( $event )" @next="defineQuantity( $event )" :value="finalValue"></ns-numpad-plus>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import nsNumpadVue from '~/components/ns-numpad.vue';
import nsNumpadPlusVue from '~/components/ns-numpad-plus.vue';

export default {
    name: 'ns-pos-quantity-popup',
    props: [ 'popup' ],
    components: {
        nsNumpad: nsNumpadVue,
        nsNumpadPlus: nsNumpadPlusVue
    },
    data() {
        return {
            finalValue: 1,
            virtualStock: null,
            options: {},
            optionsSubscription: null,
            allSelected: true,
            isLoading: false,
        }
    },
    beforeDestroy() {
        this.optionsSubscription.unsubscribe();
    },
    mounted() {
        this.optionsSubscription    =   POS.options.subscribe( options => {
            this.options    =   options;
        });

        /**
         * if the quantity is defined, then probably
         * we're already trying to edit an existing product
         */
        if ( this.popup.params.product.quantity ) {
            this.finalValue     =   this.popup.params.product.quantity;
        }

        this.popupCloser();
    },
    unmounted() {
        nsHotPress.destroy( 'pos-quantity-numpad');
        nsHotPress.destroy( 'pos-quantity-backspace' );
        nsHotPress.destroy( 'pos-quantity-enter');
    },
    methods: {
        __,

        popupCloser,

        closePopup() {
            this.popup.params.reject( false );
            this.popup.close();
        },

        updateQuantity( quantity ) {
            this.finalValue     =   quantity;
        },

        defineQuantity( quantity ) {
            /**
             * resolve is provided only on the addProductQueue
             */
            const { product, data }         =   this.popup.params;

            if ( quantity === 0 ) {
                return nsSnackBar.error( __( 'Please provide a quantity' ) )
                    .subscribe();
            }

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
                    return nsSnackBar.error( __( 'Unable to add the product, there is not enough stock. Remaining %s' ).replace( '%s', ( data.$quantities().quantity - holdQuantity ) ) )
                        .subscribe();
                }
            }

            this.resolve({ quantity });
        },

        resolve( params ) {
            this.popup.params.resolve( params );
            this.popup.close();
        }
    }
}
</script>
