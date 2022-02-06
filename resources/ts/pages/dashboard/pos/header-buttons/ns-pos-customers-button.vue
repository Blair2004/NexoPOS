<script>
import { Popup } from '@/libraries/popup';
import { default as nsPosCustomers } from '@/popups/ns-pos-customers';
import { __ } from '@/libraries/lang';

export default {
    name: 'ns-pos-customers-button',
    methods: {
        __,
        openPendingOrdersPopup() {
            const popup     =   new Popup;
            popup.open( nsPosCustomers );
        }
    }
}
</script>
<template>
    <button @click="openPendingOrdersPopup()" class="flex-shrink-0 h-12 flex items-center shadow rounded px-2 py-1 text-sm">
        <i class="mr-1 text-xl lar la-user-circle"></i>
        <span>{{ __( 'Customers' ) }}</span>
    </button>
</template>