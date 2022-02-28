<template>
    <div class="shadow-lg bg-surface-tertiary w-95vw md:w-1/3-screen">
        <div class="p-2 flex justify-between border-b">
            <h3>{{ __( 'Payment Method' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="p-2">
            <div class="py-2 p-4 text-center border border-surface-secondary bg-surface-primary text-primary rounded-lg">{{ __( 'Before submitting the payment, choose the payment type used for that order.' ) }}</div>
            <ns-field :key="index" v-for="(field,index) of fields" :field="field"></ns-field>
        </div>
        <div class="border-t p-2 flex justify-end">
            <ns-button @click="submitPayment()" type="info">{{ __( 'Submit Payment' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import popupResolver from '@/libraries/popup-resolver'
import popupCloser from '@/libraries/popup-closer'
import { __ } from '@/libraries/lang'
import FormValidation from '@/libraries/form-validation'
import { nsSnackBar } from '@/bootstrap';
import Print from '@/libraries/print';

export default {
    name: 'ns-order-instalments-payment',
    data() {
        return {
            paymentTypes,
            fields: [
                {
                    type: 'select',
                    name: 'payment_type',
                    description: __( 'Select the payment type that must apply to the current order.' ),
                    label: __( 'Payment Type' ),
                    validation: 'required',
                    options: paymentTypes
                }
            ],
            print: new Print({ settings: systemSettings, options: systemOptions, type: 'payment' }),
            validation: new FormValidation,
            order: null,
            instalment: null,
        }
    },
    methods: {
        __,
        popupResolver,
        popupCloser,

        close() {
            this.popupResolver( false );
        },

        submitPayment() {
            if( ! this.validation.validateFields( this.fields ) ) {
                return nsSnackBar.error( __m( 'The form is not valid.' ) ).subcribe();
            }

            nsHttpClient.post( `/api/nexopos/v4/orders/${this.order.id}/instalments/${this.instalment.id}/pay`, { 
                    ...this.validation.extractFields( this.fields ) 
                })
                .subscribe({
                    next: result => {
                        this.popupResolver( true );
                        this.print.printOrder( result.data.payment.id );

                        nsSnackBar.success( result.message ).subscribe();
                    },
                    error: error => {
                        nsSnackBar.error( error.message || __( 'An unexpected error has occured' ) ).subscribe();
                    }
                })
        }
    },

    mounted() {
        this.popupCloser();

        this.order      =   this.$popupParams.order;
        this.instalment =   this.$popupParams.instalment;

        this.fields     =   this.validation.createFields( this.fields );
    }
}
</script>