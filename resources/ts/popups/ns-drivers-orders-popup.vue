<template>
    <div class="ns-box w-[95vw] md:w-[60vw] lg:w-[40vw] xl:w-[30vw] h-[95vh] md:h-[80vh] shadow flex flex-col">
        <div class="p-2 flex justify-between items-center ns-box-header border-b">
            <h3>{{  __( 'Order: {name}' ).replace( '{name}', order.code ) }}</h3>
            <div>
                <ns-close-button @click="popupResolver( false )"></ns-close-button>
            </div>
        </div>
        <div class="box-body overflow-y-auto flex-auto">
            <div class="py-4 flex justify-center items-center" v-if="!loaded">
                <ns-spinner></ns-spinner>
            </div>
            <div v-if="fields.length > 0" class="p-2">
                <ns-field v-for="field of fields" :field="field"></ns-field>
            </div>
        </div>
        <div class="border-t ns-box-footer p-2 flex justify-end">
            <ns-button @click="updateOrder()">{{ __( 'Save' ) }}</ns-button>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';
import FormValidation from '~/libraries/form-validation';
import { nsConfirmPopup } from '~/components/components';
import { Popup } from '~/libraries/popup';

declare const nsHttpClient: any;
declare const nsSnackBar: any;

export default {
    name: 'foo-bar',
    methods: {
        __,
        popupResolver,
        popupCloser,
        updateOrder() {
            Popup.show( nsConfirmPopup, {
                title: __( 'Are you sure?' ),
                message: __( 'Are you sure you want to update the order?' ),
                onAction: () => {
                    nsHttpClient.post( `/api/drivers/order/${this.order.id}`, { order: this.order } )
                        .subscribe({
                            next: ( response ) => {
                                this.loaded = true;
                                nsSnackBar.success( __( 'Order updated successfully' ) );
                                this.popupResolver( false );
                            },
                            error: ( error ) => {
                                this.loaded = true;
                                nsSnackBar.error( error.message )
                            }
                        })
                }
            })
        },
        loadFields() {
            this.loaded = false;
            nsHttpClient.get( '/api/fields/ns.order-delivery-proof' )
                .subscribe({
                    next: ( fields ) => {
                        this.loaded = true;
                        this.fields = this.validation.createFields( fields );
                    },
                    error: ( error ) => {
                        this.loaded = true;
                        nsSnackBar.error( error.message)
                    }
                })
        }
    },
    data() {
        return {
            fields: [],
            validation: new FormValidation,
            loaded: false,
        }
    },
    mounted() {
        this.loadFields();
    },
    props: [ 'order', 'popup' ],
}
</script>