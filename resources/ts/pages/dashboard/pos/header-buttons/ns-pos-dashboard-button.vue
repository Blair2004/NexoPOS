<script>
import { __ } from '~/libraries/lang';
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
    <div class="ns-button success">
        <button @click="goToDashboard()" class="rounded shadow flex-shrink-0 h-12 flex items-center px-2 py-1 text-sm">
            <i class="mr-1 text-xl las la-tachometer-alt"></i>
            <span>{{ __( 'Dashboard' ) }}</span>
        </button>
    </div>
</template>