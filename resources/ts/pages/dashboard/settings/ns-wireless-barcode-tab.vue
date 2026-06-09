<template>
    <h1>Hello World</h1>
</template>

<script lang="ts">
export default {
    name: 'NsWireLessBarcodeSettingsTab',
    mounted() {
        this.initialize();
    },
    methods: {
        initialize() {
            // nsHttpClient.get( '/api/wireless-barcode-settings' )
        }
    }
}
</script>