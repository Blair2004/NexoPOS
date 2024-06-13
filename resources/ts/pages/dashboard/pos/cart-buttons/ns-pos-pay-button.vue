<template>
    <div @click="payOrder()" id="pay-button" class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-green-500 text-white hover:bg-green-600 border-r border-green-600 flex-auto">
        <i class="mr-2 text-2xl lg:text-xl las la-cash-register"></i> 
        <span class="text-lg hidden md:inline lg:text-2xl">{{ __( 'Pay' ) }}</span>
    </div>
</template>
<script>
export default {
    props: [ 'order' ],
    methods: {
        __,
        async payOrder() {
            const queues    =   nsHooks.applyFilters( 'ns-pay-queue', [
                ProductsQueue,
                CustomerQueue,
                TypeQueue,
                PaymentQueue
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
                    console.log( exception );
                    return false;
                }
            }
        },
    },
    mounted() {
        /**
         * let's register hotkeys
         */
         for( let shortcut in nsShortcuts ) {
            if ([ 
                    'ns_pos_keyboard_payment', 
                ].includes( shortcut ) ) {
                nsHotPress
                    .create( 'ns_pos_keyboard_payment' )
                    .whenNotVisible([ '.is-popup' ])
                    .whenPressed( nsShortcuts[ shortcut ] !== null ? nsShortcuts[ shortcut ].join( '+' ) : null, ( event ) => {
                        event.preventDefault();
                        this.payOrder();
                });
            }
        }
    },
    unmounted() {
        nsHotPress.destroy( 'ns_pos_keyboard_payment' );
    }
}
</script>