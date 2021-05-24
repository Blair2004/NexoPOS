<template>
    <div class="w-6/7-screen md:w-5/7-screen lg:w-3/7-screen h-6/7-screen md:h-5/7-screen lg:h-4/7-screen shadow-lg bg-white flex flex-col relative">
        <div class="p-2 border-b border-gray-200 flex justify-between items-center">
            <h2 class="font-semibold">{{ __( 'New Transaction' ) }}</h2>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto">
            <div class="h-full w-full flex items-center justify-center" v-if="fields.length === 0">
                <ns-spinner></ns-spinner>
            </div>
            <div class="p-2" v-if="fields.length > 0">
                <ns-field :field="field" v-for="(field, index) of fields" :key="index"></ns-field>
            </div>
        </div>
        <div class="p-2 bg-white justify-between border-t border-gray-200 flex">
            <div></div>
            <div class="px-1">
                <div class="-mx-2 flex flex-wrap">
                    <div class="px-1">
                        <ns-button type="danger" @click="close()">{{ __( 'Close' ) }}</ns-button>
                    </div>
                    <div class="px-1">
                        <ns-button type="info" @click="proceed()">{{ __( 'Proceed' ) }}</ns-button>
                    </div>
                </div>
            </div>
        </div>
        <div class="h-full w-full absolute flex items-center justify-center" v-if="isSubmiting === 0" style="background: rgb(0 98 171 / 45%)">
            <ns-spinner></ns-spinner>
        </div>
    </div>
</template>
<script>
import closeWithOverlayClicked from "@/libraries/popup-closer";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import FormValidation from '@/libraries/form-validation';
import { __ } from '@/libraries/lang';

export default {
    mounted() {
        this.closeWithOverlayClicked();
        this.loadTransactionFields();
    },
    data() {
        return {
            fields: [],
            isSubmiting: false,
            formValidation: new FormValidation
        }
    },
    methods: {
        __,

        closeWithOverlayClicked,

        proceed() {
            const customer      =   this.$popupParams.customer;
            const form          =   this.formValidation.extractFields( this.fields );
            this.isSubmiting    =   true;

            nsHttpClient.post( `/api/nexopos/v4/customers/${customer.id}/account-history`, form )
                .subscribe( result => {
                    this.isSubmiting    =   false;
                    nsSnackBar.success( result.message ).subscribe();
                    this.$popupParams.resolve( result );
                    this.$popup.close();
                }, ( error ) => {
                    this.isSubmiting    =   false;
                    nsSnackBar.error( error.message ).subscribe();
                    this.$popupParams.reject( error );
                })
        },

        close() {
            this.$popup.close();
            this.$popupParams.reject( false );
        },

        loadTransactionFields() {
            nsHttpClient.get( '/api/nexopos/v4/fields/ns.customers-account' )
                .subscribe( fields => {
                    this.fields     =   this.formValidation.createFields( fields );
                })
        }
    }
}
</script>