<script>
import { Popup } from '../../../../libraries/popup';
import NsPosPendingOrdersPopup from './../popups/ns-pos-pending-orders-popup';

export default {
    name: 'ns-pos-customers-button',
    methods: {
        openPendingOrdersPopup() {
            const popup     =   new Popup;
            popup.open( NsPosPendingOrdersPopup );
        }
    }
}
</script>
<template>
    <button @click="openPendingOrdersPopup()" class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
        <i class="mr-1 text-xl lar la-user-circle"></i>
        <span>Customers</span>
    </button>
</template>