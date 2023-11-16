<script>
import { Popup } from '@/libraries/popup';
import nsPosOrderTypePopupVue from '@/popups/ns-pos-order-type-popup.vue';
import { __ } from '@/libraries/lang';

export default {
    name: 'ns-pos-delivery-button',
    methods: {
        __,
        openOrderTypeSelection() {
            const popup     =   new Popup({
                primarySelector: '#pos-app',
                popupClass : 'shadow-lg bg-white w-3/5 md:w-2/3 lg:w-2/5 xl:w-2/4',
            });
            popup.open( nsPosOrderTypePopupVue );
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
                    .whenPressed( nsShortcuts[ shortcut ], ( event ) => {
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