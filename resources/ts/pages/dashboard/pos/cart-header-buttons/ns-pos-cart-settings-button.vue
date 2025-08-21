<template>
    <div class="ns-button">
        <button @click="defineOrderSettings()" class="w-full h-10 px-3 outline-hidden flex items-center">
            <i class="las la-tools"></i>
            <span class="ml-1 hidden md:inline-block">{{ __( 'Settings' ) }}</span>
        </button>
    </div>
</template>

<script lang="ts">
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import { nsSnackBar } from '~/bootstrap';
import nsPosOrderSettingsVue from '~/popups/ns-pos-order-settings.vue';
import ActionPermissions from '~/libraries/action-permissions';

declare const POS;

export default {
    name: 'ns-pos-cart-settings-button',
    props: {
        order: {
            type: Object,
            required: true
        },
        settings: {
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
        async defineOrderSettings() {
            if ( ! this.settings.edit_settings ) {
                return nsSnackBar.error( __( 'You\'re not allowed to edit the order settings.' ) );
            }

            /**
             * We'll check if the user has the right to define order settings.
             */
            await ActionPermissions.canProceed( 'defineOrderSettings' );

            try {
                const response  =   await new Promise<{}>( ( resolve, reject) => {
                    Popup.show( nsPosOrderSettingsVue, { resolve, reject, order : this.order });
                });

                /**
                 * We'll update the order
                 */
                POS.order.next({ ...this.order, ...response });

            } catch( exception ) {
                // we shouldn't catch any exception here.
            }
        }
    }
}
</script>
