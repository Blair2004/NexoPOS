<script>
import { Popup } from '@/libraries/popup';
import NsPosPendingOrdersPopup from '@/popups/ns-pos-pending-orders-popup';

export default {
    name: 'ns-pos-pending-orders-button',
    methods: {
        openPendingOrdersPopup() {
            const popup     =   new Popup;
            popup.open( NsPosPendingOrdersPopup );
        }
    },
}
</script>
<template>
    <button @click="openPendingOrdersPopup()" class="flex-shrink-0 h-12 flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
        <i class="mr-1 text-xl lar la-hand-pointer"></i>
        <span>Orders</span>
    </button>
</template>