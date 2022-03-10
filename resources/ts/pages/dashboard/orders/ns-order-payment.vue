<template>
    <div class="">
        <div class="flex -mx-4 flex-wrap">
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-12 py-1 px-2 flex justify-between items-center elevation-surface info border text-xl font-bold">
                    <span>{{ __( 'Total' ) }}</span>
                    <span>{{ order.total | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  elevation-surface success border text-xl font-bold">
                    <span>{{ __( 'Paid' ) }}</span>
                    <span>{{ order.tendered | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  elevation-surface error border text-xl font-bold">
                    <span>{{ __( 'Unpaid' ) }}</span>
                    <span v-if="order.total - order.tendered > 0">{{ order.total - order.tendered | currency }}</span>
                    <span v-if="order.total - order.tendered <= 0">{{ 0 | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-12 py-1 px-2 flex justify-between items-center  elevation-surface warning border text-xl font-bold">
                    <span>{{ __( 'Customer Account' ) }}</span>
                    <span>{{ order.customer.account_amount | currency }}</span>
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
                            {{ inputValue | currency }}
                        </div>
                        <ns-numpad :floating="true" @next="submitPayment( $event )" @changed="updateValue( $event )" :value="inputValue"></ns-numpad>
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
                        <span>{{ paymentsLabels[ payment.identifier ] || __( 'Unknown' ) }}</span>
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
import { __ } from '@/libraries/lang';
export default {
    props: [ 'order' ],
    data() {
        return {
            labels: new Labels,
            validation: new FormValidation,
            inputValue: 0,
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
                message: __( 'You make a payment for {amount}. A payment can\'t be canceled. Would you like to proceed ?' ).replace( '{amount}', this.$options.filters.currency( value ) ),
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