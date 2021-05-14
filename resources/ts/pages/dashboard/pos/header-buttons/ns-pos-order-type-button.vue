<script>
import { Popup } from '@/libraries/popup';
import nsPosOrderTypePopupVue from '@/popups/ns-pos-order-type-popup.vue';
import { __ } from '@/libraries/lang';

export default {
    name: 'ns-pos-delivery-button',
    methods: {
        __,
        openPendingOrdersPopup() {
            const popup     =   new Popup({
                primarySelector: '#pos-app',
                popupClass : 'shadow-lg bg-white w-3/5 md:w-2/3 lg:w-2/5 xl:w-2/4',
            });
            popup.open( nsPosOrderTypePopupVue );
        }
    }
}
</script>
<template>
    <button @click="openPendingOrdersPopup()" class="flex-shrink-0 h-12 flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
        <i class="mr-1 text-xl las la-truck"></i>
        <span>{{ __( 'Order Type' ) }}</span>
    </button>
</template>