<template>
    <div class="ns-box rounded shadow">
        <div class="ns-box-header border-b flex justify-between items-center p-2">
            <h3>{{ __('Drivers Overview') }}</h3>
            <div class="flex -mx-1">
                <div class="px-1">
                    <ns-icon-button class="widget-handle las la-arrows-alt"></ns-icon-button>
                </div>
                <div class="px-1">
                    <ns-close-button @click="$emit('onRemove')" />
                </div>
            </div>
        </div>
        <div class="ns-box-body p-2">
            <div>
                <h3 class="text-lg">{{ __('Available Deliveries') }}</h3>
                <h2 class="text-3xl font-bold">102</h2>
            </div>
            <div>
                <h3 class="text-lg">{{ __('Monthly Delivery') }}</h3>
                <h2 class="text-3xl font-bold">30</h2>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
declare const __;
export default {
    name: 'DriversWidgetComponent',
    methods: {
        __,
    }
}
</script>
