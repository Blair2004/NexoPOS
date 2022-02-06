<script>
import { __ } from '@/libraries/lang';
export default {
    name: 'ns-pos-dashboard-button',
    methods: {
        __,
        goToDashboard() {
            return document.location = POS.settings.getValue()[ 'urls' ][ 'dashboard_url' ];
        }
    }
}
</script>
<template>
    <button @click="goToDashboard()" class="flex-shrink-0 h-12 flex items-center shadow rounded px-2 py-1 text-sm">
        <i class="mr-1 text-xl las la-tachometer-alt"></i>
        <span>{{ __( 'Dashboard' ) }}</span>
    </button>
</template>