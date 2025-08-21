<template>
    <div class="ns-button">
        <button @click="selectCoupon()" class="w-full h-10 px-3 outline-hidden flex items-center">
            <i class="las la-tags"></i>
            <span class="ml-1 hidden md:inline-block">{{ __( 'Coupons' ) }}</span>
            <span v-if="order.coupons && order.coupons.length > 0" class="ml-1 rounded-full flex items-center justify-center h-6 w-6 bg-info-secondary text-white">{{ order.coupons.length }}</span>
        </button>
    </div>
</template>

<script lang="ts">
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import nsPosCouponsLoadPopupVue from '~/popups/ns-pos-coupons-load-popup.vue';
import ActionPermissions from '~/libraries/action-permissions';

export default {
    name: 'ns-pos-cart-coupons-button',
    props: {
        order: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            __
        }
    },
    methods: {
        async selectCoupon() {
            /**
             * We'll check if the user has the right to manage coupons.
             */
            await ActionPermissions.canProceed( 'nexopos.cart.coupons' );

            try {
                const response  =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosCouponsLoadPopupVue, { resolve, reject })
                })
            } catch( exception ) {
                // something happened
            }
        }
    }
}
</script>
