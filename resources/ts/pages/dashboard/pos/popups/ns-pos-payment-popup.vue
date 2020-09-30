<script>
import FormValidation from '../../../../libraries/form-validation';
import resolveIfQueued from "@/libraries/popup-resolver";
import { default as CashPayment } from "./../payments/cash-payment";
import { default as CreditCardPayment } from "./../payments/creditcard-payment";

export default {
    name: 'ns-pos-payment',
    data() {
        return { 
            paymentTypesSubscription: null,
            paymentsType: [],
            currentPaymentComponent: null,
        } 
    },
    computed: {
        activePayment() {
            let payment;
            return ( payment = this.paymentsType.filter( p => p.selected ) ).length > 0 ? payment[0] : false;
        }
    },
    mounted() {
        this.$popup.event.subscribe( action => {
            switch( action.event ) {
                case 'click-overlay': 
                    this.closePopup();
                break;
            }
        });

        this.paymentTypesSubscription   =   POS.paymentsType.subscribe( paymentsType => {
            this.paymentsType   =   paymentsType;
        });
    },
    watch: {
        activePayment( value ) {
            this.loadPaymentComponent( value );
        }
    },
    destroyed() {
        this.paymentTypesSubscription.unsubscribe();
    },    
    methods: {
        resolveIfQueued,

        loadPaymentComponent( payment ) {
            switch( payment.identifier ) {
                case 'cash-payment':
                    this.currentPaymentComponent    =   CashPayment;
                break;
                case 'creditcard-payment':
                    this.currentPaymentComponent    =   CreditCardPayment;
                break;
            }
        },

        closePopup() {
            this.$popup.close();
        },
        select( payment ) {
            POS.setPaymentActive( payment );
            console.log( this.activePayment );
        }
    }
}
</script>
<template>
    <div class="flex bg-white shadow-xl w-6/7-screen h-6/7-screen">
        <div class="w-56 bg-gray-300 h-full">
            <h3 class="text-xl text-center my-8 text-gray-700">Payments Gateway</h3>
            <ul>
                <li @click="select( payment )" v-for="payment of paymentsType" :class="payment.selected ? 'bg-blue-400 text-white' : 'text-gray-700'" :key="payment.identifier" class="cursor-pointer hover:bg-gray-400 py-2 px-3">{{ payment.label }}</li>
            </ul>
        </div>
        <div class="overflow-hidden flex flex-col flex-auto">
            <div class="flex flex-auto overflow-y-auto">
                <div class="flex-auto w-1/2">
                    <component v-bind:is="currentPaymentComponent"></component>
                </div>
                <hr class="border-r border-gray-200 h-full">
                <div class="flex-auto w-1/2">
                    order summary
                </div>
            </div>
            <div class="flex w-full bg-gray-300 justify-between p-2">
                <div>
                    
                </div>
                <div>
                    <ns-button type="info">Submit Payment</ns-button>
                </div>
            </div>
        </div>
    </div>
</template>