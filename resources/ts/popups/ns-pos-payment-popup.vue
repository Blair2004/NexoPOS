<script lang="ts">
import { nsSnackBar } from '~/bootstrap';
import resolveIfQueued from "~/libraries/popup-resolver";
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import CashPayment from "~/pages/dashboard/pos/payments/cash-payment.vue";
import CreditCardPayment from "~/pages/dashboard/pos/payments/creditcard-payment.vue";
import BankPayment from '~/pages/dashboard/pos/payments/bank-payment.vue';
import AccountPayment from '~/pages/dashboard/pos/payments/account-payment.vue';
import nsPosLoadingPopupVue from './ns-pos-loading-popup.vue';
import samplePaymentVue from '~/pages/dashboard/pos/payments/sample-payment.vue';
import nsSelectPopupVue from './ns-select-popup.vue';
import { nsCurrency, nsRawCurrency } from '~/filters/currency';
import { ref } from 'vue';
import { nsConfirmPopup } from '~/components/components';

declare const POS, nsHooks, nsCloseButton, nsButton, shallowRef;

export default {
    name: 'ns-pos-payment',
    props: [ 'popup' ],
    data() {
        return { 
            paymentTypesSubscription: null,
            paymentsType: [],
            activePayment: null,
            order: null,
            showPayment: false,
            orderSubscription: null,
            currentPaymentComponent: null,
            activePaymentSubscription: null,
        } 
    },
    computed: {
        expectedPayment() {
            const minimalPaymentPercent     =   this.order.customer.group.minimal_credit_payment;
            return ( this.order.total * minimalPaymentPercent ) / 100;
        }
    },
    mounted() {
        this.orderSubscription          =   POS.order.subscribe( order => {
            this.order  =   ref( order );
        });

        this.activePaymentSubscription  =   POS.selectedPaymentType.subscribe( activePayment => {
            this.activePayment = activePayment;
            if ( activePayment !== null ) {
                this.loadPaymentComponent( activePayment );
            }
        });
        this.paymentTypesSubscription   =   POS.paymentsType.subscribe( paymentsType => {
            this.paymentsType   =   paymentsType;
            paymentsType.filter( payment => {
                if ( payment.selected ) {
                    POS.selectedPaymentType.next( payment );
                }
            });
        });

        nsHooks.doAction( 'ns-pos-payment-mounted', this );
    },
    unmounted() {
        this.activePaymentSubscription.unsubscribe();
        this.paymentTypesSubscription.unsubscribe();
        this.orderSubscription.unsubscribe();

        nsHooks.doAction( 'ns-pos-payment-destroyed', this );
    },    
    methods: {
        __, 
        nsCurrency,
        
        resolveIfQueued,

        loadPaymentComponent( payment ) {
            switch( payment.identifier ) {
                case 'cash-payment':
                    this.currentPaymentComponent    =   shallowRef( CashPayment );
                break;
                case 'creditcard-payment':
                    this.currentPaymentComponent    =   shallowRef( CreditCardPayment );
                break;
                case 'bank-payment':
                    this.currentPaymentComponent    =   shallowRef( BankPayment );
                break;
                case 'account-payment':
                    this.currentPaymentComponent    =   shallowRef( AccountPayment );
                break;
                default: 
                    this.currentPaymentComponent    =   shallowRef( samplePaymentVue );
                break;
            }
        },
        async selectPayment() {
            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, {
                        label: __( 'Select Payment Gateway' ),
                        options: this.paymentsType.map( payment => {
                            return {
                                label: payment.label,
                                value: payment.identifier
                            }
                        }),
                        value: this.activePayment.identifier,
                        resolve, reject
                    })
                });

                this.select( this.paymentsType.filter( p => p.identifier === result[0].value )[0] );
            } catch( exception ) {
                // not necessary to throw an error.
            }
        },
        select( payment ) {
            this.showPayment    =   false;
            POS.setPaymentActive( payment );
        },
        closePopup() {
            console.log( this.popup );
            this.popup.close();
            POS.selectedPaymentType.next( null );
        },
        deletePayment( payment ) {
            POS.removePayment( payment );
        },
        selectPaymentAsActive( event ) {
            this.select( this.paymentsType.filter( payment => payment.identifier === event.target.value )[0] );
        },
        // no payment is necessary here so we'll proceed
        async submiAsUnpaid() {
            let confirmResponse;
            try {
                confirmResponse = await new Promise( ( resolve ) => {
                    const response = Popup.show( nsConfirmPopup, {
                        title: __( 'Save As Unpaid' ),
                        message: __( 'Are you sure you want to save this order as unpaid?' ),
                        onAction: ( action ) => {
                            resolve(action)
                        }
                    })
                })
            } catch( exception ) {
                nsSnackBar.error( exception.message || __( 'An unexpected error occured while saving the order as unpaid.' ) ).subscribe();
                console.log( exception );
                // ...
            }

            /**
             * The use hasn't confirmed the action
             * so we'll return false
             */
            if ( ! confirmResponse ) {
                return false;
            }

            const popup     =   Popup.show( nsPosLoadingPopupVue );
            
            try {

                /**
                 * if there is any payment defined
                 * we might need to remove that and refresh the order
                 */
                POS.order.next({ ...POS.order.getValue(), payments: [] });
                POS.refreshCart();
                const result: { message: string, data: any } = await new Promise( ( resolve, reject ) => {
                    POS.proceedSubmitting( POS.order.getValue(), resolve, reject );
                });

                popup.close();
                this.popup.close();
                nsSnackBar.success( result.message ).subscribe();
                POS.printOrderReceipt( result.data.order, 'silent' );
            } catch( exception ) {
                popup.close();
                // show error message
                nsSnackBar.error( exception.message || __( 'An error occured while saving the order as unpaid.' ) ).subscribe();
            }
        },
        getPaymentLabel( payment ) {
            const foundPayment = this.paymentsType.filter( p => p.identifier === payment.identifier )[0];

            if ( foundPayment ) {
                return foundPayment.label;
            }

            return payment.identifier;
        },
        submitOrder( data = {}) {
            const popup     =   Popup.show( nsPosLoadingPopupVue );
            
            try {

                const order     =   { ...POS.order.getValue(), ...data };

                POS.submitOrder( order ).then( result => {
                    // close spinner
                    popup.close();

                    nsSnackBar.success( result.message ).subscribe();

                    POS.printOrderReceipt( result.data.order, 'silent' );
    
                    // close payment popup
                    this.popup.close();
                }, ( error ) => {
                    // close loading popup
                    popup.close();
    
                    // show error message
                    nsSnackBar.error( error.message ).subscribe();
                });
            } catch( exception ) {
                popup.close();
    
                // show error message
                nsSnackBar.error( exception.message || __( 'An unexpected error occured while submitting the order.' ) ).subscribe();
                console.log( exception );
            }
        }
    }
}
</script>
<template>
    <div id="ns-payment-popup" class="w-screen h-screen p-8 flex overflow-hidden" v-if="order">
        <div class="flex flex-col flex-auto lg:flex-row shadow-xl">
            <div class="w-full lg:w-56 lg:h-full flex justify-between px-2 lg:px-0 lg:block items-center lg:items-start">
                <h3 class="lg:hidden text-xl text-center my-4 font-bold lg:my-8">{{ __( 'Gateway' ) }} <span v-if="activePayment">: {{ activePayment.label }}</span></h3>
                <div class="h-16 hidden lg:block"></div>
                <ul class="hidden lg:block">
                    <li @click="select( payment )" v-for="payment of paymentsType" :class="payment.selected && ! showPayment ? 'ns-visible' : ''" :key="payment.identifier" class="cursor-pointer ns-payment-gateway py-2 px-3">{{ payment.label }}</li>
                    <li v-if="paymentsType.length > 0" @click="showPayment = true" :class="showPayment ? 'ns-visible' : ''" class="cursor-pointer py-2 px-3 ns-payment-list border-t mt-4 flex items-center justify-between">
                        <span>{{ __( 'Payment List' ) }}</span>
                        <span class="px-2 rounded-full h-8 w-8 flex items-center justify-center ns-label">{{ order.payments.length }}</span>
                    </li> 
                </ul>
                <ns-close-button class="lg:hidden" @click="closePopup()"></ns-close-button>
            </div>
            <div class="overflow-hidden flex flex-col flex-auto">
                <div class="flex flex-col flex-auto overflow-hidden">
                    <div class="h-12 hidden items-center justify-between lg:flex">
                        <div>
                            <h3 class="text-xl hidden lg:block text-center my-4 font-bold lg:my-8">{{ __( 'Gateway' ) }} <span class="hidden-md" v-if="activePayment">: {{ activePayment.label }}</span></h3>
                        </div>
                        <div class="px-2">
                            <ns-close-button @click="closePopup()"></ns-close-button>
                        </div>
                    </div>
                    <div class="flex flex-auto ns-payment-wrapper overflow-y-auto" v-if="! showPayment && activePayment">
                        <component 
                            @submit="submitOrder()" 
                            :label="activePayment.label" 
                            :identifier="activePayment.identifier" 
                            v-bind:is="currentPaymentComponent"></component>
                    </div>
                    <div class="flex flex-auto items-center justify-center bg-white" v-if="! activePayment">
                        <div>
                            <h3 class="font-bold text-center text-3xl">{{ __( 'Unable to Proceed') }}</h3>
                            <p class="text-center">{{  __( 'Your system doesn\'t have any valid Payment Type. Consider creating one and try again.' ) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-auto ns-payment-wrapper overflow-y-auto p-2 flex-col" v-if="showPayment">
                        <h3 class="text-center font-bold py-2">{{ __( 'List Of Payments' ) }}</h3>
                        <ul class="flex-auto">
                            <li v-if="order.payments.length === 0" class="p-2 flex justify-center mb-2 items-center">
                                <h3 class="font-semibold">{{ __( 'No Payment added.' ) }}</h3>
                            </li>
                            <li :key="index" v-for="(payment,index) of order.payments" class="p-2 flex justify-between mb-2 items-center">
                                <span>{{ getPaymentLabel( payment ) }}</span>
                                <div class="flex items-center">
                                    <span>{{ nsCurrency( payment.value ) }}</span>
                                    <button v-if="! payment.id" @click="deletePayment( payment )" class="error rounded-full h-8 w-8 flex items-center justify-center ml-2">
                                        <i class="las la-trash-alt"></i>
                                    </button>
                                    <button v-if="payment.id" class="default rounded-full h-8 w-8 flex items-center justify-center ml-2">
                                        <i class="las la-lock"></i>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-if="activePayment" class="flex lg:hidden ns-payment-buttons">
                    <button @click="selectPayment()" class="flex items-center justify-center w-1/3 text-2xl flex-auto h-12 font-bold ns-payment-type-button">
                        <span class="text-sm">{{ __( 'Payment Type' ) }}</span>
                    </button>
                    <button v-if="order.tendered >= order.total" @click="submitOrder()" class="flex items-center justify-center w-1/3 text-2xl flex-auto h-12 ns-submit-button font-bold">
                        <span class="text-sm">{{ __( 'Submit Payment' ) }}</span>
                    </button>
                    <button v-if="order.tendered < order.total" @click="submitOrder({ payment_status: 'unpaid' })" class="flex items-center justify-center w-1/3 text-2xl flex-auto h-12 ns-layaway-button font-bold">
                        <span class="text-sm">{{ __( 'Layaway' ) }}</span>
                    </button>
                    <button @click="showPayment = true" class="w-1/3 flex ns-payment-button text-2xl flex-auto h-12 items-center justify-center font-bold">
                        <span class="text-sm mr-1">{{ __( 'Payment List' ) }}</span>
                        <span class="px-2 rounded-full h-6 w-6 text-xs flex items-center justify-center ns-label">{{ order.payments.length }}</span>
                    </button>
                </div>
                <div v-if="activePayment" class="flex-col sm:flex-row w-full ns-payment-footer justify-end p-2 hidden lg:flex">
                    <div class="flex justify-end">
                        <ns-button v-if="order.tendered >= order.total" @click="submitOrder()" :type="order.tendered >= order.total ? 'success' : 'info'">
                            <span ><i class="las la-cash-register"></i> {{ __( 'Submit Payment' ) }}</span>
                        </ns-button>
                        <div v-if="order.tendered < order.total" class="flex -mx-2">
                            <div class="px-2">
                                <ns-button v-if="order.tendered === 0" @click="submitOrder({ payment_status: 'unpaid' })" :type="order.tendered >= order.total ? 'success' : 'info'">
                                    <span><i class="las la-bookmark"></i> {{ __( 'Layaway' ) }} &mdash; {{ nsCurrency( expectedPayment ) }}</span>
                                </ns-button>                         
                                <ns-button v-if="order.tendered > 0" @click="submitOrder({ payment_status: 'unpaid' })" type="info">
                                    <span><i class="las la-save"></i> {{ __( 'Update' ) }}</span>
                                </ns-button>                         
                            </div>
                            <div class="px-2" v-if="order.tendered === 0">
                                <ns-button @click="submiAsUnpaid()" :type="'info'">
                                    <span><i class="las la-hands-helping"></i> {{ __( 'Save As Unpaid' ) }}</span>
                                </ns-button>                         
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>