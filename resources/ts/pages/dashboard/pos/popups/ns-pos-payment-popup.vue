<script>
import FormValidation from '../../../../libraries/form-validation';
import resolveIfQueued from "@/libraries/popup-resolver";
import { default as CashPayment } from "./../payments/cash-payment";
import { default as CreditCardPayment } from "./../payments/creditcard-payment";
import { default as PaymentsHistory } from "./../payments/history-payment";

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
                case 'history-payment':
                    this.currentPaymentComponent    =   PaymentsHistory;
                break;
            }
        },
        select( payment ) {
            POS.setPaymentActive( payment );
            console.log( this.activePayment );
        },
        closePopup() {
            this.$popup.close();
        }
    }
}
</script>
<template>
    <div class="w-screen h-screen p-4 flex overflow-hidden">
        <div class="flex flex-col flex-auto lg:flex-row bg-white shadow-xl">
            <div class="w-full lg:w-56 bg-gray-300 lg:h-full flex justify-between px-2 lg:px-0 lg:block items-center lg:items-start">
                <h3 class="text-xl text-center my-4 font-bold lg:my-8 text-gray-700">Payments Gateway</h3>
                <ul class="hidden lg:block">
                    <li @click="select( payment )" v-for="payment of paymentsType" :class="payment.selected ? 'bg-white text-gray-800' : 'text-gray-700'" :key="payment.identifier" class="cursor-pointer hover:bg-gray-400 py-2 px-3">{{ payment.label }}</li>
                    <li class="cursor-pointer text-gray-700 hover:bg-gray-400 py-2 px-3 border-t border-gray-400 mt-4">Payment List</li> 
                </ul>
                <button @click="closePopup()" class="cursor-pointer md:hidden rounded-full border-2 border-blue-400 text-blue-400 bg-blue-200 hover:border-red-600 hover:bg-red-400 hover:text-red-600 h-10 w-10 flex justify-center items-center">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="overflow-hidden flex flex-col flex-auto">
                <div class="flex flex-col flex-auto overflow-hidden">
                    <div class="h-12 bg-gray-300 hidden items-center justify-between lg:flex">
                        <div></div>
                        <div class="px-2">
                            <button @click="closePopup()" class="cursor-pointer rounded-full border-2 border-blue-400 text-blue-400 bg-blue-200 hover:border-red-600 hover:bg-red-400 hover:text-red-600 h-10 w-10 flex justify-center items-center">
                                <i class="las la-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-auto overflow-y-auto">
                        <component v-bind:is="currentPaymentComponent"></component>
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
    </div>
</template>