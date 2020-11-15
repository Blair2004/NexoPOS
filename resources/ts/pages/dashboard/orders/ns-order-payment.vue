<template>
    <div class="">
        <div class="flex -mx-4 flex-wrap">
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-12 py-1 px-2 flex justify-between items-center bg-blue-400 text-white text-xl font-bold">
                    <span>Total</span>
                    <span>{{ order.total | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  bg-green-400 text-white text-xl font-bold">
                    <span>Paid</span>
                    <span>{{ order.tendered | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  bg-red-400 text-white text-xl font-bold">
                    <span>Unpaid</span>
                    <span v-if="order.total - order.tendered > 0">{{ order.total - order.tendered | currency }}</span>
                    <span v-if="order.total - order.tendered <= 0">{{ 0 | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  bg-teal-400 text-white text-xl font-bold">
                    <span>Customer Account</span>
                    <span>{{ order.customer.account_amount | currency }}</span>
                </div>
            </div>
        </div>
        <div class="flex -mx-4 flex-wrap">
            <div class="px-2 w-full mb-4 md:w-1/2">
                <div v-if="order.payment_status !== 'paid'">
                    <h3 class="font-semibold border-b-2 border-blue-400 py-2">
                        Payment
                    </h3>
                    <div class="py-2">
                        <ns-field v-for="(field, index) of fields" :field="field" :key="index"></ns-field>
                        <div class="my-2 px-2 h-12 flex justify-end items-center bg-gray-200">
                            {{ inputValue | currency }}
                        </div>
                        <ns-numpad @next="submitPayment( $event )" @changed="updateValue( $event )" :value="inputValue"></ns-numpad>
                    </div>
                </div>
                <div v-if="order.payment_status === 'paid'" class="flex items-center justify-center h-full">
                    <h3 class="text-gray-700 font-semibold">No payment possible for paid order.</h3>
                </div>
            </div>
            <div class="px-2 w-full mb-4 md:w-1/2">
                <h3 class="font-semibold border-b-2 border-blue-400 py-2 mb-2">
                    Payment History
                </h3>
                <ul>
                    <li v-for="payment of order.payments" :key="payment.id" class="p-2 flex items-center justify-between text-shite bg-gray-300 mb-2">
                        <span>{{ payment.identifier }}</span>
                        <span>{{ payment.value | currency }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script>
import nsNumpad from "@/components/ns-numpad.vue";
import Labels from '@/libraries/labels';
import FormValidation from '@/libraries/form-validation';
import nsPosConfirmPopupVue from '@/popups/ns-pos-confirm-popup.vue';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
export default {
    props: [ 'order' ],
    data() {
        return {
            labels: new Labels,
            validation: new FormValidation,
            inputValue: 0,
            fields: []
        }
    },
    methods: {
        updateValue( value ) {
            this.inputValue     =   value;
        },
        loadPaymentFields() {
            nsHttpClient.get( '/api/nexopos/v4/orders/payments' )
                .subscribe( fields => {
                    this.fields     =   this.validation.createFields( fields );
                });
        },
        submitPayment( value ) {
            this.validation.validateFields( this.fields );

            if ( ! this.validation.fieldsValid( this.fields ) ) {
                return nsSnackBar.error( 'Unable to proceed the form is not valid' ).subscribe();
            }

            if ( parseFloat( value ) == 0 ) {
                return nsSnackBar.error( 'Please provide a valid value' ).subscribe();
            }
        
            value   =   parseFloat( value );

            const form  =   {
                ...this.validation.extractFields( this.fields ),
                value
            }

            Popup.show( nsPosConfirmPopupVue, {
                title: 'Confirm Your Action',
                message: 'You make a payment for {amount}. A payment can\'t be canceled. Would you like to proceed ?'.replace( '{amount}', this.$options.filters.currency( value ) ),
                onAction:  ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( `/api/nexopos/v4/orders/${this.order.id}/payments`, form )
                            .subscribe( result => {
                                nsSnackBar.success( result.message ).subscribe();
                                this.$emit( 'changed' );
                            }, error => {
                                nsSnackBar.error( error.message ).subscribe();
                            })
                    }
                }
            });
        }
    },
    components: {
        nsNumpad
    },
    mounted() {
        this.loadPaymentFields();
    }
}
</script>