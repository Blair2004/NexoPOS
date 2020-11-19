<script>
import FormValidation from '@/libraries/form-validation';
import resolveIfQueued from "@/libraries/popup-resolver";
import { default as CashPayment } from "@/pages/dashboard/pos/payments/cash-payment";
import { default as CreditCardPayment } from "@/pages/dashboard/pos/payments/creditcard-payment";
import { default as BankPayment } from '@/pages/dashboard/pos/payments/bank-payment.vue';
import { default as AccountPayment } from '@/pages/dashboard/pos/payments/account-payment.vue';
import { Popup } from '@/libraries/popup';
import nsPosLoadingPopupVue from './ns-pos-loading-popup.vue';
import { nsSnackBar } from '@/bootstrap';

export default {
    name: 'ns-pos-payment',
    data() {
        return { 
            paymentTypesSubscription: null,
            paymentsType: [],
            order: null,
            showPayment: false,
            orderSubscription: null,
            currentPaymentComponent: null,
        } 
    },
    computed: {
        activePayment() {
            let payment;
            return ( payment = this.paymentsType.filter( p => p.selected ) ).length > 0 ? payment[0] : false;
        },
        expectedPayment() {
            const minimalPaymentPercent     =   this.order.customer.group.minimal_credit_payment;
            return ( this.order.total * minimalPaymentPercent ) / 100;
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

        this.orderSubscription      =   POS.order.subscribe( order => this.order = order );

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
        this.orderSubscription.unsubscribe();
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
                case 'bank-payment':
                    this.currentPaymentComponent    =   BankPayment;
                break;
                case 'account-payment':
                    this.currentPaymentComponent    =   AccountPayment;
                break;
            }
        },
        select( payment ) {
            this.showPayment    =   false;
            POS.setPaymentActive( payment );
        },
        closePopup() {
            this.$popup.close();
        },
        deletePayment( payment ) {
            POS.removePayment( payment );
        },
        selectPaymentAsActive( event ) {
            this.select( this.paymentsType.filter( payment => payment.identifier === event.target.value )[0] );
        },
        submitOrder( data = {}) {
            const popup     =   Popup.show( nsPosLoadingPopupVue );
            
            try {

                const order     =   { ...POS.order.getValue(), ...data };

                POS.submitOrder( order ).then( result => {
                    // close spinner
                    popup.close();

                    nsSnackBar.success( result.message ).subscribe();

                    POS.printOrder( result.data.order.id );
    
                    // close payment popup
                    this.$popup.close();
                }, ( error ) => {
                    // close loading popup
                    popup.close();
    
                    // show error message
                    nsSnackBar.error( error.message ).subscribe();
                });
            } catch( exception ) {
                popup.close();
    
                // show error message
                nsSnackBar.error( error.message ).subscribe();
            }
        }
    }
}
</script>
<template>
    <div class="w-screen h-screen p-4 flex overflow-hidden" v-if="order">
        <div class="flex flex-col flex-auto lg:flex-row bg-white shadow-xl">
            <div class="w-full lg:w-56 bg-gray-300 lg:h-full flex justify-between px-2 lg:px-0 lg:block items-center lg:items-start">
                <h3 class="text-xl text-center my-4 font-bold lg:my-8 text-gray-700">Payments Gateway</h3>
                <ul class="hidden lg:block">
                    <li @click="select( payment )" v-for="payment of paymentsType" :class="payment.selected && ! showPayment ? 'bg-white text-gray-800' : 'text-gray-700'" :key="payment.identifier" class="cursor-pointer hover:bg-gray-400 py-2 px-3">{{ payment.label }}</li>
                    <li @click="showPayment = true" :class="showPayment ? 'bg-white text-gray-800' : 'text-gray-700'" class="cursor-pointer text-gray-700 hover:bg-gray-400 py-2 px-3 border-t border-gray-400 mt-4 flex items-center justify-between">
                        <span>Payment List</span>
                        <span class="px-2 rounded-full h-8 w-8 flex items-center justify-center bg-green-500 text-white">{{ order.payments.length }}</span>
                    </li> 
                </ul>
                <ns-close-button class="lg:hidden" @click="closePopup()"></ns-close-button>
                <!-- <button @click="closePopup()" class="cursor-pointer lg:hidden rounded-full border-2 border-blue-400 text-blue-400 bg-blue-200 hover:border-red-600 hover:bg-red-400 hover:text-red-600 h-10 w-10 flex justify-center items-center">
                    <i class="las la-times"></i>
                </button> -->
            </div>
            <div class="overflow-hidden flex flex-col flex-auto">
                <div class="flex flex-col flex-auto overflow-hidden">
                    <div class="h-12 bg-gray-300 hidden items-center justify-between lg:flex">
                        <div></div>
                        <div class="px-2">
                            <ns-close-button @click="closePopup()"></ns-close-button>
                        </div>
                    </div>
                    <div class="flex flex-auto overflow-y-auto" v-if="! showPayment">
                        <component @submit="submitOrder()" :label="activePayment.label" :identifier="activePayment.identifier" v-bind:is="currentPaymentComponent"></component>
                    </div>
                    <div class="flex flex-auto overflow-y-auto p-2 flex-col" v-if="showPayment">
                        <h3 class="text-center font-bold py-2 text-gray-700">List Of Payments</h3>
                        <ul class="flex-auto">
                            <li v-if="order.payments.length === 0" class="p-2 bg-gray-200 flex justify-center mb-2 items-center">
                                <h3 class="font-semibold">No Payment added.</h3>
                            </li>
                            <li :key="index" v-for="(payment,index) of order.payments" class="p-2 bg-gray-200 flex justify-between mb-2 items-center">
                                <span>{{ payment.label}}</span>
                                <div class="flex items-center">
                                    <span>{{ payment.value | currency }}</span>
                                    <button @click="deletePayment( payment )" class="rounded-full bg-red-400 h-8 w-8 flex items-center justify-center text-white ml-2">
                                        <i class="las la-trash-alt"></i>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="flex w-full bg-gray-300 justify-between p-2">
                    <div class="flex">
                        <div class="flex items-center lg:hidden">
                            <h3 class="font-semibold mr-2">Select Payment</h3>
                            <select @change="selectPaymentAsActive( $event )" class="p-2 rounded border-2 border-blue-400 bg-white shadow">
                                <option value="">Choose Payment</option>
                                <option :selected="activePayment.identifier === payment.identifier" :value="payment.identifier" :key="payment.identifier" @click="select( payment )" v-for="payment of paymentsType">{{ payment.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <ns-button v-if="order.tendered >= order.total" @click="submitOrder()" :type="order.tendered >= order.total ? 'success' : 'info'">
                            <span ><i class="las la-cash-register"></i> Submit Payment</span>
                        </ns-button>
                        <ns-button v-if="order.tendered < order.total" @click="submitOrder({ payment_status: 'unpaid' })" :type="order.tendered >= order.total ? 'success' : 'info'">
                            <span><i class="las la-bookmark"></i> Layaway &mdash; {{ expectedPayment | currency }}</span>
                        </ns-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>