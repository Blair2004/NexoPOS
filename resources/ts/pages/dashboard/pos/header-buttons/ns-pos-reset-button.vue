<script>
import { Popup } from '@/libraries/popup';
import { default as nsPosCustomers } from '@/popups/ns-pos-customers';
import nsPosConfirmPopupVue from '@/popups/ns-pos-confirm-popup.vue';
import popupCloser from "@/libraries/popup-closer";

export default {
    name: 'ns-pos-customers-button',
    mounted() {
        this.popupCloser();
    },
    methods: {
        __,
        
        popupCloser,

        reset() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'The current order will be cleared. But not deleted if it\'s persistent. Would you like to proceed ?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        POS.reset();
                    }
                }
            });
        }
    }
}
</script>
<template>
    <div class="ns-button error">
        <button @click="reset()" class="rounded shadow flex-shrink-0 h-12 flex items-center px-2 py-1 text-sm">
            <i class="mr-1 text-xl  las la-eraser"></i>
            <span>{{ __( 'Reset' ) }}</span>
        </button>
    </div>
</template>