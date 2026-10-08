<template>
    <div id="pos-print-choice-popup" class="rounded-lg overflow-hidden flex flex-col shadow-lg w-[71.43vw] md:w-[57.14vw] lg:w-[28.57vw]">
        <div class="flex items-center justify-center flex-col flex-auto p-4">
            <h2 class="text-xl md:text-2xl text-center">{{ __( 'Print Document' ) }}</h2>
            <p class="py-4 text-sm md:text-base text-center">{{ __( 'Which document would you like to print for this sale?' ) }}</p>
            <p class="text-sm text-center">
                {{ __( 'Preselected:' ) }}
                <strong>{{ defaultDocument === 'invoice' ? __( 'Invoice' ) : __( 'Receipt' ) }}</strong>
            </p>
        </div>
        <div class="action-buttons flex justify-end border-t p-4">
            <button class="rounded font-bold cancel px-3 py-1 h-10 flex items-center justify-center" @click="choose( 'none' )">{{ __( "Don't print" ) }}</button>
            <button
                class="rounded font-bold px-3 py-1 h-10 flex items-center justify-center"
                :class="defaultDocument === 'receipt' ? 'border-2 border-info-primary' : ''"
                @click="choose( 'receipt' )">{{ __( 'Receipt' ) }}</button>
            <button
                class="rounded font-bold px-3 py-1 h-10 flex items-center justify-center"
                :class="defaultDocument === 'invoice' ? 'border-2 border-info-primary' : ''"
                @click="choose( 'invoice' )">{{ __( 'Invoice' ) }}</button>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';

declare const POS;

export default {
    name: 'ns-pos-print-choice',
    props: [ 'popup' ],
    data() {
        return {
            defaultDocument: 'receipt',
            settled: false,
        }
    },
    mounted() {
        this.defaultDocument  =   this.popup.params.defaultDocument === 'invoice' ? 'invoice' : 'receipt';

        this.popupCloser();
    },
    methods: {
        __,
        popupCloser,

        choose( document ) {
            this.settled   =   true;

            /**
             * "none" skips the printing entirely, any other
             * value prints the requested document variant.
             */
            if ( document === 'receipt' || document === 'invoice' ) {
                POS.printSaleDocument( this.popup.params.order.id, document );
            }

            this.popup.close();
        },
    },
    unmounted() {
        /**
         * When the popup is dismissed without an explicit choice
         * (ESC, overlay click or close button), fall back to the
         * configured Printed Document ("Print Selection").
         */
        if ( ! this.settled && this.popup.params.order ) {
            POS.printSaleDocument( this.popup.params.order.id, this.defaultDocument );
        }
    }
}
</script>
