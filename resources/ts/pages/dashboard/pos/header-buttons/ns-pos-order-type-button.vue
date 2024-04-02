<script>
import { Popup } from '~/libraries/popup';
import nsPosOrderTypePopupVue from '~/popups/ns-pos-order-type-popup.vue';
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-pos-delivery-button',
    methods: {
        __,
        openOrderTypeSelection() {
            Popup.show( nsPosOrderTypePopupVue );
        }
    },
    beforeDestroy() {
        nsHotPress.destroy( 'ns_pos_keyboard_order_type' );
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
                    'ns_pos_keyboard_order_type', 
                ].includes( shortcut ) ) {
                nsHotPress
                    .create( 'ns_pos_keyboard_order_type' )
                    .whenNotVisible([ '.is-popup' ])
                    .whenPressed( nsShortcuts[ shortcut ] !== null ? nsShortcuts[ shortcut ].join( '+' ) : null, ( event ) => {
                        event.preventDefault();
                        this.openOrderTypeSelection();
                });
            }
        }
    }
}
</script>
<template>
    <div class="ns-button default">
        <button @click="openOrderTypeSelection()" class="rounded shadow flex-shrink-0 h-12 flex items-center px-2 py-1 text-sm">
            <i class="mr-1 text-xl las la-truck"></i>
            <span>{{ __( 'Order Type' ) }}</span>
        </button>
    </div>
</template>