<template>
    <div class="ns-button">
        <button @click="openNotePopup()" class="w-full h-10 px-3 outline-hidden">
            <i class="las la-comment"></i>
            <span class="ml-1 hidden md:inline-block">{{ __( 'Comments' ) }}</span>
        </button>
    </div>
</template>

<script lang="ts">
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import { nsSnackBar } from '~/bootstrap';
import nsPosNotePopupVue from '~/popups/ns-pos-note-popup.vue';
import ActionPermissions from '~/libraries/action-permissions';

declare const POS;

export default {
    name: 'ns-pos-cart-comment-button',
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
        async openNotePopup() {
            /**
             * We'll ensure the user has the right to add comments to an order.
             */
            await ActionPermissions.canProceed( 'nexopos.cart.comments' );
            
            try {
                const response  =   await new Promise<{}>( ( resolve, reject ) => {
                    const note              =   this.order.note;
                    const note_visibility   =   this.order.note_visibility;
                    Popup.show( nsPosNotePopupVue, { resolve, reject, note, note_visibility });
                });

                const order     =   { ...this.order, ...response };
                POS.order.next( order );
            } catch( exception ) {
                if ( exception !== false ) {
                    nsSnackBar.error( exception.message );
                }
            }
        }
    }
}
</script>
