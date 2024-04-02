<script>
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import { defineAsyncComponent } from 'vue';

export default {
    name: 'ns-pos-customers-button',
    methods: {
        __,
        openCustomerPopup() {
            Popup.show( defineAsyncComponent({
                loader: () => import( '~/popups/ns-pos-customers.vue' )
            }) );
        }
    },
    beforeDestroy() {
        nsHotPress.destroy( 'ns_pos_keyboard_create_customer' );
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
                    'ns_pos_keyboard_create_customer', 
                ].includes( shortcut ) ) {
                nsHotPress
                    .create( 'ns_pos_keyboard_create_customer' )
                    .whenNotVisible([ '.is-popup' ])
                    .whenPressed( nsShortcuts[ shortcut ] !== null ? nsShortcuts[ shortcut ].join( '+' ) : null, ( event ) => {
                        event.preventDefault();
                        this.openCustomerPopup();
                });
            }
        }
    }
}
</script>
<template>
    <div class="ns-button default">
        <button @click="openCustomerPopup()" class="rounded shadow flex-shrink-0 h-12 flex items-center px-2 py-1 text-sm">
            <i class="mr-1 text-xl lar la-user-circle"></i>
            <span>{{ __( 'Customers' ) }}</span>
        </button>
    </div>
</template>