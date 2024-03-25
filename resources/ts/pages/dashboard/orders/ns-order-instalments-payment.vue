<template>
    <div class="shadow-lg ns-box w-95vw md:w-2/3-screen lg:w-1/3-screen">
        <div class="p-2 flex justify-between border-b items-center">
            <h3>{{ __( 'Payment Method' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 ns-box-body">
            <ns-notice color="info" class="py-2 p-4 text-center border text-primary rounded-lg">{{ __( 'Before submitting the payment, choose the payment type used for that order.' ) }}</ns-notice>
            <br>
            <ns-field :key="index" v-for="(field,index) of fields" :field="field"></ns-field>
        </div>
        <div class="border-t ns-box-footer p-2 flex justify-end">
            <ns-button @click="submitPayment()" type="info">{{ __( 'Submit Payment' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import popupResolver from '~/libraries/popup-resolver'
import popupCloser from '~/libraries/popup-closer'
import { __ } from '~/libraries/lang'
import FormValidation from '~/libraries/form-validation'
import { nsSnackBar } from '~/bootstrap';
import Print from '~/libraries/print';
import { nsNotice } from '~/components/components'
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue'

export default {
    name: 'ns-order-instalments-payment',
    props: [ 'popup' ],
    components: {
        nsNotice,
    },
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
            print: new Print({ urls: systemUrls, options: systemOptions }),
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

        updateInstalmentAsDue( instalment ) {
            nsHttpClient.put( `/api/orders/${this.order.id}/instalments/${this.instalment.id}/`, {
                instalment: {
                    date: ns.date.moment.format('YYYY-MM-DD HH:mm:ss' )
                }
            }).subscribe({
                next: result => {
                    this.submitPayment();
                },
                error: error => {
                    nsSnackBar.error( error.message || __( 'An unexpected error has occurred' ) ).subscribe();
                }
            })
        },

        submitPayment() {
            if( ! this.validation.validateFields( this.fields ) ) {
                return nsSnackBar.error( __m( 'The form is not valid.' ) ).subcribe();
            }

            nsHttpClient.post( `/api/orders/${this.order.id}/instalments/${this.instalment.id}/pay`, { 
                    ...this.validation.extractFields( this.fields ) 
                })
                .subscribe({
                    next: result => {
                        this.popupResolver( true );
                        this.print.exec( result.data.payment.id, 'payment' );

                        nsSnackBar.success( result.message ).subscribe();
                    },
                    error: error => {
                        if ( error.status === 'error' ) {
                            Popup.show( nsPosConfirmPopupVue, {
                                title: __( 'Update Instalment Date' ),
                                message: __( 'Would you like to mark that instalment as due today ? If you confirm the instalment will be marked as paid.' ),
                                onAction: ( action ) => {
                                    if ( action ) {
                                        this.updateInstalmentAsDue( this.instalment );
                                    }
                                }
                            });
                        }

                        nsSnackBar.error( error.message || __( 'An unexpected error has occurred' ) ).subscribe();
                    }
                })
        }
    },

    mounted() {
        this.popupCloser();

        this.order      =   this.popup.params.order;
        this.instalment =   this.popup.params.instalment;

        this.fields     =   this.validation.createFields( this.fields );
    }
}
</script>
