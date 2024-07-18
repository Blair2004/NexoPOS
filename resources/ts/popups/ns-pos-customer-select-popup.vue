<template>
    <div id="ns-pos-customer-select-popup" class="ns-box shadow-xl w-4/5-screen md:w-2/5-screen xl:w-108">
        <div id="header" class="border-b ns-box-header text-center font-semibold text-2xl py-2">
            <h2>{{ __( 'Select Customer' ) }}</h2>
        </div>
        <div class="relative">
            <div class="p-2 border-b ns-box-body items-center flex justify-between">
                <span>{{ __( 'Selected' ) }} : </span>
                <div class="flex items-center justify-between">
                    <span>{{ order.customer ? `${order.customer.first_name} ${order.customer.last_name}` : 'N/A' }}</span>
                    <button v-if="order.customer" @click="openCustomerHistory( order.customer, $event )" class="mx-2 rounded-full h-8 w-8 flex items-center justify-center border ns-inset-button hover:border-transparent">
                        <i class="las la-eye"></i>
                    </button>
                </div>
            </div>
            <div class="p-2 border-b ns-box-body flex justify-between text-primary">
                <div class="input-group flex-auto border-2 rounded">
                    <input
                        ref="searchField" 
                        @keydown.enter="attemptToChoose()"
                        v-model="searchCustomerValue"
                        placeholder="Search Customer" 
                        type="text" 
                        class="outline-none w-full p-2">
                </div>
            </div>
            <div class="h-3/5-screen xl:h-2/5-screen overflow-y-auto ns-scrollbar">
                <ul class="ns-vertical-menu">
                    <li class="p-2 text-center text-primary" v-if="customers && customers.length === 0">
                        {{ __( 'No customer match your query...' ) }}
                    </li>
                    <li @click="createCustomerWithMatch( searchCustomerValue )" class="p-2 cursor-pointer text-center text-primary" v-if="customers && customers.length === 0">
                        <span class="border-b border-dashed border-info-primary">{{ __( 'Create a customer' ) }}</span>
                    </li>
                    <li @click="selectCustomer( customer )" v-for="customer of customers" :key="customer.id" class="cursor-pointer p-2 border-b text-primary flex justify-between items-center">
                        <div class="flex flex-col">
                            <span>{{ customer.first_name }} {{ customer.last_name }}</span>
                            <small class="text-xs text-secondary" v-if="customer.group">{{ customer.group.name }}</small>
                            <small class="text-xs text-secondary" v-else>{{ __( 'No Group Assigned' ) }}</small>
                        </div>
                        <p class="flex items-center">
                            <span v-if="customer.owe_amount > 0" class="text-error-primary">-{{ nsCurrency( customer.owe_amount ) }}</span>
                            <span v-if="customer.owe_amount > 0">/</span>
                            <span class="purchase-amount">{{ nsCurrency( customer.purchases_amount ) }}</span>
                            <button @click="openCustomerHistory( customer, $event )" class="mx-2 rounded-full h-8 w-8 flex items-center justify-center border ns-inset-button info">
                                <i class="las la-eye"></i>
                            </button>
                        </p>
                    </li>
                </ul>
            </div>
            <div v-if="isLoading" class="z-10 top-0 absolute w-full h-full flex items-center justify-center">
                <ns-spinner size="24" border="8"></ns-spinner>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import resolveIfQueued from "~/libraries/popup-resolver";
import { Popup } from '~/libraries/popup';
import nsPosCustomersVue from './ns-pos-customers.vue';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';
import popupCloser from '~/libraries/popup-closer';

declare const POS;

export default {
    props: [ 'popup' ],
    data() {
        return {
            searchCustomerValue: '',
            orderSubscription: null,
            order: {},
            debounceSearch: null,
            customers: [],
            isLoading: false,
        }
    },
    computed: {
        customerSelected() {
            return false;
        }
    },
    watch: {
        searchCustomerValue( value ) {
            clearTimeout( this.debounceSearch );
            this.debounceSearch     =   setTimeout( () => {
                this.searchCustomer( value );
            }, 500 );
        }
    },
    mounted() {
        this.orderSubscription  =   POS.order.subscribe( order => {
            this.order      =   order;
        });

        this.getRecentCustomers();

        this.$refs.searchField.focus();

        this.popupCloser();
    },
    unmounted() {
        this.orderSubscription.unsubscribe();
    },
    methods: {
        __,
        popupCloser,
        nsCurrency,
        
        /**
         * if the popup is likely to be used
         * on a queue, using the resolveIfQueued
         * could help being notified when it's closed.
         */
        resolveIfQueued,

        attemptToChoose() {
            if ( this.customers.length === 1 ) {
                return this.selectCustomer( this.customers[0] );
            }
            nsSnackBar.info( __( 'Too many results.' ) ).subscribe();
        },

        openCustomerHistory( customer, event ) {
            event.stopImmediatePropagation();
            this.popup.close();
            Popup.show( nsPosCustomersVue, { customer, activeTab: 'account-payment' });
        },

        selectCustomer( customer ) {
            this.customers.forEach( customer => customer.selected = false );
            customer.selected   =   true;

            /**
             * define the customer using the default
             * POS object;
             */
            this.isLoading      =   true;

            POS.selectCustomer( customer ).then( resolve => {
                this.isLoading  =   false;
                this.resolveIfQueued( customer );
            }).catch( error => {
                this.isLoading  =   false;
            });
        },
        searchCustomer( value ) {
            nsHttpClient.post( '/api/customers/search', {
                search: value
            }).subscribe( customers => {
                customers.forEach( customer => customer.selected = false );
                this.customers  =   customers;
            })
        },

        createCustomerWithMatch( value ) {
            this.resolveIfQueued(false)
            Popup.show( nsPosCustomersVue, { name: value })
        },

        getRecentCustomers() {
            this.isLoading  =   true;

            nsHttpClient.get( '/api/customers/recently-active' )
                .subscribe({
                    next: customers => {
                        this.isLoading  =   false;
                        customers.forEach( customer => customer.selected = false );
                        this.customers  =   customers;
                    },
                    error: ( error ) => {
                        this.isLoading  =   false;
                    }
                });
        }
    }
}
</script>