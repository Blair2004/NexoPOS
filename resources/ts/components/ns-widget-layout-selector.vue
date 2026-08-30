<template>
    <label
        v-if="canResize"
        class="relative inline-flex items-center rounded-full border border-box-edge text-fontcolor shadow-sm hover:bg-box-elevation-hover focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20"
        @pointerdown.stop
        @keydown.stop>
        <span class="">{{ __( 'Widget size' ) }}</span>
        <select
            :value="widget.layout.name"
            :aria-label="__( 'Widget size' )"
            class="cursor-pointer appearance-none rounded-full bg-transparent py-1 pl-2 pr-6 text-xs font-semibold text-fontcolor outline-none"
            @change="handleChange">
            <option
                class="bg-input-background"
                v-for="layout in widget.supportedLayouts"
                :key="layout.name"
                :value="layout.name">
                {{ layout.columns }} × {{ layout.rows }}
            </option>
        </select>
        <i class="las la-angle-down pointer-events-none absolute right-2 text-xs text-fontcolor-soft" aria-hidden="true"></i>
    </label>
</template>

<script lang="ts">
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-widget-layout-selector',
    props: {
        widget: {
            type: Object,
            required: true,
        },
    },
    computed: {
        canResize(): boolean {
            return this.widget.supportedLayouts?.length > 1
                && typeof this.widget.requestLayoutChange === 'function';
        },
    },
    methods: {
        __,
        handleChange( event: Event ) {
            this.widget.requestLayoutChange( ( event.target as HTMLSelectElement ).value );
        },
    },
};
</script>
