<template>
    <div class="ns-box w-[90vw] md:w-[60vw] lg:w-[40vw] xl:w-[30vw]">
        <div class="ns-box-header flex justify-between items-center border-b p-2 mb-2">
            <h3>{{ __('Driver Orders Options') }}</h3>
            <ns-close-button @click="$emit('onRemove')" />
        </div>
        <div class="ns-box-body">
            <ns-tabs>
                <ns-tab-item :label="__( 'Manage' )" identifier="delivery">
                    

                </ns-tab-item>
                <ns-tab-item :label="__( 'Address' )" identifier="address">
                    

                </ns-tab-item>
                <ns-tab-item :label="__( 'Map' )" identifier="map">
                    

                </ns-tab-item>
            </ns-tabs>
        </div>
    </div>
</template>
<script lang="ts">
declare const __;
export default {
    name: 'NsDriversOrdersOptionsPopup',
    methods: { __ }
}
</script>
