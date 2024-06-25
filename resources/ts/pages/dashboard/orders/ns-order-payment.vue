<template>
    <div class="flex -mx-4 flex-wrap">
        <div class="px-2 w-full md:w-1/2">
            <div class="my-1 h-12 py-1 px-2 flex justify-between items-center elevation-surface info border text-xl font-bold">
                <span>{{ __( 'Total' ) }}</span>
                <span>{{ nsCurrency( order.total ) }}</span>
            </div>
        </div>
        <div class="px-2 w-full md:w-1/2">
            <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  elevation-surface success border text-xl font-bold">
                <span>{{ __( 'Paid' ) }}</span>
                <span>{{ nsCurrency( order.tendered ) }}</span>
            </div>
        </div>
        <div class="px-2 w-full md:w-1/2">
            <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  elevation-surface error border text-xl font-bold">
                <span>{{ __( 'Unpaid' ) }}</span>
                <span v-if="order.total - order.tendered > 0">{{ nsCurrency( order.total - order.tendered ) }}</span>
                <span v-if="order.total - order.tendered <= 0">{{ nsCurrency( 0 ) }}</span>
            </div>
        </div>
        <div class="px-2 w-full md:w-1/2">
            <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  elevation-surface warning border text-xl font-bold">
                <span>{{ __( 'Customer Account' ) }}</span>
                <span>{{ nsCurrency( order.customer.account_amount ) }}</span>
            </div>
        </div>
    </div>
    <div class="flex -mx-4 flex-wrap">
        <div class="px-2 w-full mb-4 md:w-1/2">
            <div v-if="order.payment_status !== 'paid'">
                <h3 class="font-semibold border-b-2 border-info-primary py-2">
                    {{ __( 'Payment' ) }}
                </h3>
                <div class="py-2">
                    <ns-field v-for="(field, index) of fields" :field="field" :key="index"></ns-field>
                    <div class="my-2 px-2 h-12 flex justify-end items-center border elevation-surface">
                        {{ nsCurrency( inputValue ) }}
                    </div>
                    <ns-numpad-plus :floating="true" @next="submitPayment( $event )" @changed="updateValue( $event )" :value="inputValue"></ns-numpad-plus>
                </div>
            </div>
            <div v-if="order.payment_status === 'paid'" class="flex items-center justify-center h-full">
                <h3 class="text-primary font-semibold">{{ __( 'No payment possible for paid order.' ) }}</h3>
            </div>
        </div>
        <div class="px-2 w-full mb-4 md:w-1/2">
            <h3 class="font-semibold border-b-2 border-info-primary py-2 mb-2">
                {{ __( 'Payment History' ) }}
            </h3>
            <ul>
                <li v-for="payment of order.payments" :key="payment.id" class="p-2 flex items-center justify-between text-shite border elevation-surface mb-2">
                    <span class="flex items-center">
                        <a href="javascript:void(0)" @click="printPaymentReceipt( payment )" class="m-1 rounded-full hover:bg-info-tertiary hover:text-white flex items-center justify-center h-8 w-8">
                            <i class="las la-print"></i>
                        </a>
                        {{ paymentsLabels[ payment.identifier ] || __( 'Unknown' ) }}
                    </span>
                    <span>{{ nsCurrency( payment.value ) }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
<script lang="ts">
import Labels from '~/libraries/labels';
import FormValidation from '~/libraries/form-validation';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';
import { nsNumpad } from '~/components/components';
import Print from '~/libraries/print';

declare const systemUrls, systemOptions, paymentTypes, Popup;

export default {
    props: [ 'order' ],
    data() {
        return {
            labels: new Labels,
            validation: new FormValidation,
            inputValue: 0,
            print: new Print({ urls: systemUrls, options: systemOptions }),
            fields: [],
            paymentTypes, // must be exposed on the local environment
        }
    },
    computed: {
        paymentsLabels() {
            const labels    =   new Object;

            this.paymentTypes.forEach( payment => {
                labels[ payment.value ]     =   payment.label;
            })

            return labels;
        }
    },
    methods: {
        __,
        nsCurrency,

        updateValue( value ) {
            this.inputValue     =   value;
        },
        loadPaymentFields() {
            nsHttpClient.get( '/api/orders/payments' )
                .subscribe( fields => {
                    this.fields     =   this.validation.createFields( fields );
                });
        },
        printPaymentReceipt( payment ) {
            this.print.process( payment.id, 'payment' );
        },
        submitPayment( value ) {
            this.validation.validateFields( this.fields );

            if ( ! this.validation.fieldsValid( this.fields ) ) {
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid' ) ).subscribe();
            }

            if ( parseFloat( value ) == 0 ) {
                return nsSnackBar.error( __( 'Please provide a valid value' ) ).subscribe();
            }
        
            value   =   parseFloat( value );

            const form  =   {
                ...this.validation.extractFields( this.fields ),
                value
            }

            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'You make a payment for {amount}. A payment can\'t be canceled. Would you like to proceed ?' ).replace( '{amount}', nsCurrency( value ) ),
                onAction:  ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( `/api/orders/${this.order.id}/payments`, form )
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