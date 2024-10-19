<template>
    <div @click="payOrder()" id="pay-button" class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-green-500 text-white hover:bg-green-600 border-r border-green-600 flex-auto">
        <i class="mr-2 text-2xl lg:text-xl las la-cash-register"></i> 
        <span class="text-lg hidden md:inline lg:text-2xl">{{ __( 'Pay' ) }}</span>
    </div>
</template>
<script lang="ts">
declare const POS;
declare const nsShortcuts;
declare const nsHotPress;
declare const __;

export default {
    props: [ 'order' ],
    methods: {
        __,
        async payOrder() {
            POS.runPaymentQueue();
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