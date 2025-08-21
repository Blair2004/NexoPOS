<template>
    <div class="ns-button">
        <button @click="selectTaxGroup()" class="w-full h-10 px-3 outline-hidden flex items-center">
            <i class="las la-balance-scale-left"></i>
            <span class="ml-1 hidden md:inline-block">{{ __( 'Taxes' ) }}</span>
            <span v-if="order.taxes && order.taxes.length > 0" class="ml-1 rounded-full flex items-center justify-center h-6 w-6 bg-info-secondary text-white">{{ order.taxes.length }}</span>
        </button>
    </div>
</template>

<script lang="ts">
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import nsPosTaxPopupVue from '~/popups/ns-pos-tax-popup.vue';
import ActionPermissions from '~/libraries/action-permissions';

declare const POS;

export default {
    name: 'ns-pos-cart-taxes-button',
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
        async selectTaxGroup( activeTab = 'settings' ) {
            /**
             * We'll check if the user has the right to manage taxes.
             */
            await ActionPermissions.canProceed( 'nexopos.cart.taxes' );

            try {
                const response              =   await new Promise<{}>( ( resolve, reject ) => {
                    const taxes             =   this.order.taxes;
                    const tax_group_id      =   this.order.tax_group_id;
                    const tax_type          =   this.order.tax_type;
                    Popup.show( nsPosTaxPopupVue, { resolve, reject, taxes, tax_group_id, tax_type, activeTab })
                });

                const order             =   { ...this.order, ...response };
                POS.order.next( order );
            } catch( exception ) {
                // we don't catch any exception for this.
            }
        }
    }
}
</script>
