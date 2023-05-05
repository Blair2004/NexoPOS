<template>
    <div class="ns-box shadow-lg w-6/7-screen md:w-3/7-screen lg:w-2/6-screen">
        <div class="p-2 flex ns-box-header justify-between border-b items-center">
            <h3 class="font-semibold">{{ __( 'Hold Order' ) }}</h3>
            <div>
                <ns-close-button @click="popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto ns-box-body">
            <div class="border-b h-16 flex items-center justify-center">
                <span class="text-5xl text-primary">{{ nsCurrency( order.total ) }}</span>
            </div>
            <div class="p-2">
                <div class="input-group border-2 info">
                    <input @keyup.enter="submitHold()" v-model="title" ref="reference" type="text" :placeholder="__( 'Order Reference' )" class="outline-none rounded border-2 p-2 w-full">
                </div>
            </div>
            <div class="p-2">
                <p class="text-secondary">
                    {{ __( `The current order will be set on hold. You can retrieve this order from the pending order button. Providing a reference to it might help you to identify the order more quickly.` )}}
                </p>
            </div>
        </div>
        <div class="flex ns-box-footer">
            <div @click="submitHold()" class=" cursor-pointer w-1/2 py-3 flex justify-center items-center bg-green-500 text-white font-semibold">
                {{ __( 'Confirm' ) }}
            </div>
            <div @click="popup.close()" class="cursor-pointer w-1/2 py-3 flex justify-center items-center bg-error-secondary text-white font-semibold">
                {{ __( 'Cancel' ) }}
            </div>
        </div>
    </div>
</template>
<script>
import popupCloser from "~/libraries/popup-closer";
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'ns-pos-hold-orders',
    props: [ 'popup' ],
    data() {
        return {
            order: {},
            title: '',
            show: true,
        }
    },
    mounted() {
        this.popupCloser();

        this.show   =   POS.getHoldPopupEnabled(); // if the popup is enabled, it will be displayed.

        /**
         * if the popup won't show
         * we'll resolve immediately
         */
        if ( ! this.show ) {
            this.popup.params.resolve({ title: this.title });
        }

        this.$refs[ 'reference' ].focus();
        this.$refs[ 'reference' ].select();

        this.order  =   this.popup.params.order;
        this.title  =   this.popup.params.order.title || '';
    },
    methods: {
        __,
        nsCurrency,

        popupCloser,

        submitHold() {
            this.popup.close();
            this.popup.params.resolve({ title: this.title });
        }
    }
}
</script>
