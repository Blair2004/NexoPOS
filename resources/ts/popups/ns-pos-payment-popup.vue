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
import { __ } from '@/libraries/lang';
import samplePaymentVue from '@/pages/dashboard/pos/payments/sample-payment.vue';

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

        this.order                      =   this.$popupParams.order;
        this.paymentTypesSubscription   =   POS.paymentsType.subscribe( paymentsType => {
            this.paymentsType   =   paymentsType;
            paymentsType.filter( payment => {
                if ( payment.selected ) {
                    POS.selectedPaymentType.next( payment );
                }
            });
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
        __, 
        
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
                default: 
                    this.currentPaymentComponent    =   samplePaymentVue;
                break;
            }
        },
        select( payment ) {
            this.showPayment    =   false;
            POS.setPaymentActive( payment );
        },
        closePopup() {
            this.$popup.close();
            POS.selectedPaymentType.next( null );
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

                    POS.printOrderReceipt( result.data.order );
    
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
        <div class="flex flex-col flex-auto lg:flex-row bg-surface-tertiary shadow-xl">
            <div class="w-full lg:w-56 bg-surface-tertiary lg:h-full flex justify-between px-2 lg:px-0 lg:block items-center lg:items-start">
                <h3 class="text-xl text-center my-4 font-bold lg:my-8 text-primary">{{ __( 'Payments Gateway' ) }}</h3>
                <ul class="hidden lg:block">
                    <li @click="select( payment )" v-for="payment of paymentsType" :class="payment.selected && ! showPayment ? 'bg-surface-quaternary text-primary' : 'text-primary'" :key="payment.identifier" class="cursor-pointer hover:bg-surface-secondary py-2 px-3">{{ payment.label }}</li>
                    <li @click="showPayment = true" :class="showPayment ? 'bg-surface-quaternary text-primary' : 'text-primary'" class="cursor-pointer text-primary hover:bg-surface-secondary py-2 px-3 border-t border-surface-secondary mt-4 flex items-center justify-between">
                        <span>{{ __( 'Payment List' ) }}</span>
                        <span class="px-2 rounded-full h-8 w-8 flex items-center justify-center bg-success-primary text-white">{{ order.payments.length }}</span>
                    </li> 
                </ul>
                <ns-close-button class="lg:hidden" @click="closePopup()"></ns-close-button>
            </div>
            <div class="overflow-hidden flex flex-col flex-auto">
                <div class="flex flex-col flex-auto overflow-hidden">
                    <div class="h-12 bg-surface-tertiary hidden items-center justify-between lg:flex">
                        <div></div>
                        <div class="px-2">
                            <ns-close-button @click="closePopup()"></ns-close-button>
                        </div>
                    </div>
                    <div class="flex flex-auto bg-surface-quaternary overflow-y-auto" v-if="! showPayment">
                        <component 
                            @submit="submitOrder()" 
                            :label="activePayment.label" 
                            :identifier="activePayment.identifier" 
                            v-bind:is="currentPaymentComponent"></component>
                    </div>
                    <div class="flex flex-auto bg-surface-quaternary overflow-y-auto p-2 flex-col" v-if="showPayment">
                        <h3 class="text-center font-bold py-2 text-primary">{{ __( 'List Of Payments' ) }}</h3>
                        <ul class="flex-auto">
                            <li v-if="order.payments.length === 0" class="p-2 bg-surface-secondary flex justify-center mb-2 items-center">
                                <h3 class="font-semibold">{{ __( 'No Payment added.' ) }}</h3>
                            </li>
                            <li :key="index" v-for="(payment,index) of order.payments" class="p-2 bg-surface-secondary flex justify-between mb-2 items-center">
                                <span>{{ payment.label}}</span>
                                <div class="flex items-center">
                                    <span>{{ payment.value | currency }}</span>
                                    <button @click="deletePayment( payment )" class="rounded-full bg-error-primary h-8 w-8 flex items-center justify-center text-white ml-2">
                                        <i class="las la-trash-alt"></i>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row w-full bg-surface-tertiary justify-between p-2">
                    <div class="flex">
                        <div class="flex items-center lg:hidden">
                            <select @change="selectPaymentAsActive( $event )" class="p-2 rounded border-2 border-info-primary bg-surface-tertiary shadow">
                                <option value="">{{ __( 'Choose Payment' ) }}</option>
                                <option :selected="activePayment.identifier === payment.identifier" :value="payment.identifier" :key="payment.identifier" @click="select( payment )" v-for="payment of paymentsType">{{ payment.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <ns-button v-if="order.tendered >= order.total" @click="submitOrder()" :type="order.tendered >= order.total ? 'success' : 'info'">
                            <span ><i class="las la-cash-register"></i> {{ __( 'Submit Payment' ) }}</span>
                        </ns-button>
                        <ns-button v-if="order.tendered < order.total" @click="submitOrder({ payment_status: 'unpaid' })" :type="order.tendered >= order.total ? 'success' : 'info'">
                            <span><i class="las la-bookmark"></i> {{ __( 'Layaway' ) }} &mdash; {{ expectedPayment | currency }}</span>
                        </ns-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>