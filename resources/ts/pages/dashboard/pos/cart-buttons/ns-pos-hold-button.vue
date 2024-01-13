<template>
    <div @click="holdOrder()" id="hold-button" class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-blue-500 text-white border-r hover:bg-blue-600 border-blue-600 flex-auto">
        <i class="mr-2 text-2xl lg:text-xl las la-pause"></i> 
        <span class="text-lg hidden md:inline lg:text-2xl">{{ __( 'Hold' ) }}</span>
    </div>
</template>

<script lang="ts">
import nsPosHoldOrdersPopupVue from '~/popups/ns-pos-hold-orders-popup.vue';
import nsPosLoadingPopupVue from "~/popups/ns-pos-loading-popup.vue";

declare const nsSnackBar;
declare const ProductsQueue;
declare const CustomerQueue;
declare const TypeQueue;
declare const Popup;
declare const nsHooks;
declare const nsHotPress;
declare const nsShortcuts;
declare const __;
declare const POS;


export default {
    props: [ 'order' ],
    methods: {
        __,
        async holdOrder() {
            if ( this.order.payment_status !== 'hold' && this.order.payments.length > 0 ) {
                return nsSnackBar.error( __( 'Unable to hold an order which payment status has been updated already.' ) ).subscribe();
            }

            const queues    =   nsHooks.applyFilters( 'ns-hold-queue', [
                ProductsQueue,
                CustomerQueue,
                TypeQueue,
            ]);

            for( let index in queues ) {
                try {
                    const promise   =   new queues[ index ]( this.order );
                    const response  =   await promise.run();
                } catch( exception ) {
                    /**
                     * in case there is something broken
                     * on the promise, we just stop the queue.
                     */
                    return false;    
                }
            }

            /**
             * overriding hold popup
             * This will be useful to inject custom 
             * hold popup.
             */
            const popup     =   nsHooks.applyFilters( 'ns-override-hold-popup', () => {
                const promise   =   new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosHoldOrdersPopupVue, { resolve, reject, order : this.order });
                });

                promise.then( result => {
                    this.order.title            =   result.title;
                    this.order.payment_status   =   'hold';
                    POS.order.next( this.order );

                    const popup     =   Popup.show( nsPosLoadingPopupVue );
                    
                    POS.submitOrder().then( result => {
                        popup.close();
                        // @todo add a print snipped here
                        nsSnackBar.success( result.message ).subscribe();
                    }, ( error ) => {
                        popup.close();
                        // @todo add a print snipped here
                        nsSnackBar.error( error.message ).subscribe();
                    });
                }).catch( exception => {
                    console.log( exception );
                })
            });

            popup();
        },
    },
    mounted() {
        /**
         * let's register hotkeys
         */
         for( let shortcut in nsShortcuts ) {
            /**
             * let's declare only shortcuts that
             * works on the pos grid and that doesn't 
             * expect any popup to be visible
             */
            if ([ 
                    'ns_pos_keyboard_hold_order', 
                ].includes( shortcut ) ) {
                nsHotPress
                    .create( 'ns_pos_keyboard_hold_order' )
                    .whenNotVisible([ '.is-popup' ])
                    .whenPressed( nsShortcuts[ shortcut ] !== null ? nsShortcuts[ shortcut ].join( '+' ) : null, ( event ) => {
                        event.preventDefault();
                        this.holdOrder();
                });
            }
        }
    },
    unmounted() {
        nsHotPress.destroy( 'ns_pos_keyboard_hold_order' );
    }
}
</script>