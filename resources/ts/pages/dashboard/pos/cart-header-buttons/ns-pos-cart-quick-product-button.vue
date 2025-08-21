<template>
    <div class="ns-button" v-if="options.ns_pos_quick_product === 'yes'">
        <button @click="openAddQuickProduct()" class="w-full h-10 px-3 outline-hidden flex items-center">
            <i class="las la-plus"></i>
            <span class="ml-1 hidden md:inline-block">{{ __( 'Product' ) }}</span>
        </button>
    </div>
</template>

<script lang="ts">
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import nsPosQuickProductPopupVue from '~/popups/ns-pos-quick-product-popup.vue';
import ActionPermissions from '~/libraries/action-permissions';

export default {
    name: 'ns-pos-cart-quick-product-button',
    props: {
        order: {
            type: Object,
            required: true
        },
        options: {
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
        async openAddQuickProduct() {
            /**
             * We'll check if the user has the right to add a quick product.
             */
            await ActionPermissions.canProceed( 'nexopos.cart.products' );
            
            try {
                const promise   =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosQuickProductPopupVue, { resolve, reject })
                });
            } catch( exception ) {
                // ...
            }
        }
    }
}
</script>
