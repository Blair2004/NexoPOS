<template>
    <div id="ns-pos-customers" class="shadow-lg rounded w-95vw h-95vh lg:w-3/5-screen flex flex-col overflow-hidden">
        <div class="ns-header p-2 flex justify-between items-center border-b">
            <h3 class="font-semibold">{{ __( 'Customers' ) }}</h3>
            <div>
                <ns-close-button @click="$popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="ns-body flex-auto flex p-2 overflow-y-auto">
            <ns-tabs :active="activeTab" @active="activeTab = $event">
                <ns-tabs-item identifier="create-customers" label="New Customer">
                    <ns-crud-form 
                        v-if="options.ns_pos_customers_creation_enabled === 'yes'"
                        @updated="prefillForm( $event )"
                        @save="handleSavedCustomer( $event )"
                        submit-url="/api/nexopos/v4/crud/ns.customers"
                        src="/api/nexopos/v4/crud/ns.customers/form-config">
                        <template v-slot:title>{{ __( 'Customer Name' ) }}</template>
                        <template v-slot:save>{{ __( 'Save Customer' ) }}</template>
                    </ns-crud-form>
                    <div v-if="options.ns_pos_customers_creation_enabled !== 'yes'" class="h-full flex-col w-full flex items-center justify-center text-primary">
                        <i class="lar la-hand-paper ns-icon text-6xl"></i>
                        <h3 class="font-medium text-2xl">{{ __( 'Not Authorized' ) }}</h3>
                        <p>{{ __( 'Creating customers has been explicitly disabled from the settings.' ) }}</p>
                    </div>
                </ns-tabs-item>
                <ns-tabs-item identifier="account-payment" :label="__( 'Customer Account' )" class="flex" style="padding:0!important">
                    <div class="flex-auto w-full flex items-center justify-center flex-col p-4" v-if="customer === null">
                        <i class="lar la-frown text-6xl"></i>
                        <h3 class="font-medium text-2xl">{{ __( 'No Customer Selected' ) }}</h3>
                        <p>{{ __( 'In order to see a customer account, you need to select one customer.' ) }}</p>
                        <div class="my-2">
                            <ns-button @click="openCustomerSelection()" type="info">{{ __( 'Select Customer' ) }}</ns-button>
                        </div>
                    </div>
                    <div v-if="customer" class="flex flex-col flex-auto">
                        <div class="flex-auto p-2 flex flex-col">
                            <div class="-mx-4 flex flex-wrap ns-tab-cards">
                                <div class="px-4 mb-4 w-full">
                                    <h2 class="font-semibold">{{ __( 'Summary For' ) }} : {{ customer.name }}</h2>
                                </div>
                                <div class="px-4 mb-4 w-full md:w-1/4">
                                    <div class="rounded-lg shadow bg-transparent bg-gradient-to-br from-success-secondary to-green-700 p-2 flex flex-col text-white">
                                        <h3 class="font-medium text-lg">{{ __( 'Total Purchases' ) }}</h3>
                                        <div class="w-full flex justify-end">
                                            <h2 class="font-bold">{{ customer.purchases_amount | currency }}</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 mb-4 w-full md:w-1/4">
                                    <div class="rounded-lg shadow bg-transparent bg-gradient-to-br from-error-secondary to-red-700 p-2 text-white">
                                        <h3 class="font-medium text-lg">{{ __( 'Total Owed' ) }}</h3>
                                        <div class="w-full flex justify-end">
                                            <h2 class="text-2xl font-bold">{{ customer.owed_amount | currency }}</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 mb-4 w-full md:w-1/4">
                                    <div class="rounded-lg shadow bg-transparent bg-gradient-to-br from-blue-500 to-blue-700 p-2 text-white">
                                        <h3 class="font-medium text-lg">{{ __( 'Account Amount' ) }}</h3>
                                        <div class="w-full flex justify-end">
                                            <h2 class="text-2xl font-bold">{{ customer.account_amount | currency }}</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 mb-4 w-full md:w-1/4">
                                    <div class="rounded-lg shadow bg-transparent bg-gradient-to-br from-teal-500 to-teal-700 p-2 text-white">
                                        <h3 class="font-medium text-lg">{{ __( 'Credit Limit' ) }}</h3>
                                        <div class="w-full flex justify-end">
                                            <h2 class="text-2xl font-bold">{{ customer.credit_limit_amount | currency }}</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-auto flex-col overflow-hidden">
                                <ns-tabs :active="selectedTab" @changeTab="doChangeTab( $event )">
                                    <ns-tabs-item identifier="orders" :label="__( 'Orders' )">
                                        <div class="py-2 w-full">
                                            <h2 class="font-semibold text-primary">{{ __( 'Last Purchases' ) }}</h2>
                                        </div>
                                        <div class="flex-auto flex-col flex overflow-hidden">
                                            <div class="flex-auto overflow-y-auto">
                                                <table class="table ns-table w-full">
                                                    <thead>
                                                        <tr class="text-primary">
                                                            <th colspan="3" width="150" class="p-2 border font-semibold">{{ __( 'Order' ) }}</th>
                                                            <th width="50" class="p-2 border font-semibold">{{ __( 'Options' ) }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="text-primary">
                                                        <tr v-if="orders.length === 0">
                                                            <td class="border p-2 text-center" colspan="4">{{ __( 'No orders...' ) }}</td>
                                                        </tr>
                                                        <tr v-for="order of orders" :key="order.id">
                                                            <td colspan="3" class="border p-2 text-center">
                                                                <div class="flex flex-col items-start">
                                                                    <h3 class="font-bold">{{ __( 'Code' ) }}: {{ order.code }}</h3>
                                                                    <div class="md:-mx-2 w-full flex flex-col md:flex-row">
                                                                        <div class="md:px-2 flex items-start w-full md:w-1/4">
                                                                            <small>{{ __( 'Total' ) }}: {{ order.total | currency }}</small>
                                                                        </div>
                                                                        <div class="md:px-2 flex items-start w-full md:w-1/4">
                                                                            <small>{{ __( 'Status' ) }}: {{ order.human_status }}</small>
                                                                        </div>
                                                                        <div class="md:px-2 flex items-start w-full md:w-1/4">
                                                                            <small>{{ __( 'Delivery' ) }}: {{ order.human_delivery_status }}</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="border p-2 text-center">
                                                                <button @click="openOrderOptions( order )" class="rounded-full h-8 px-2 flex items-center justify-center border border-gray ns-inset-button success">
                                                                    <i class="las la-wallet"></i>
                                                                    <span class="ml-1">{{ __( 'Options' ) }}</span>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </ns-tabs-item>
                                    <ns-tabs-item identifier="coupons" :label="__( 'Coupons' )">
                                        <div class="flex-auto h-full justify-center flex items-center" v-if="isLoadingCoupons">
                                            <ns-spinner size="36"></ns-spinner>
                                        </div>
                                        <template v-if="! isLoadingCoupons">
                                            <div class="py-2 w-full">
                                                <h2 class="font-semibold text-primary">{{ __( 'Coupons' ) }}</h2>
                                            </div>
                                            <div class="flex-auto flex-col flex overflow-hidden">
                                                <div class="flex-auto overflow-y-auto">
                                                    <table class="table ns-table w-full">
                                                        <thead>
                                                            <tr class="text-primary">
                                                                <th width="150" class="p-2 border font-semibold">{{ __( 'Name' ) }}</th>
                                                                <th class="p-2 border font-semibold">{{ __( 'Type' ) }}</th>
                                                                <th class="p-2 border font-semibold"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="text-primary text-sm">
                                                            <tr v-if="coupons.length === 0">
                                                                <td class="border p-2 text-center" colspan="4">{{ __( 'No coupons for the selected customer...' ) }}</td>
                                                            </tr>
                                                            <tr v-for="coupon of coupons" :key="coupon.id">
                                                                <td width="300" class="border p-2">
                                                                    <h3>{{ coupon.name }}</h3>
                                                                    <div class="">
                                                                        <ul class="-mx-2 flex">
                                                                            <li class="text-xs text-primary px-2">{{ __( 'Usage :' ) }} {{ coupon.usage }}/{{ coupon.limit_usage }}</li>
                                                                            <li class="text-xs text-primary px-2">{{ __( 'Code :' ) }} {{ coupon.code }}</li>
                                                                        </ul>
                                                                    </div>
                                                                </td>
                                                                <td class="border p-2 text-center">{{ getType( coupon.coupon.type ) }} 
                                                                    <span v-if="coupon.coupon.type === 'percentage_discount'">
                                                                        ({{ coupon.coupon.discount_value }}%)
                                                                    </span>
                                                                    <span v-if="coupon.coupon.type === 'flat_discount'">
                                                                        ({{ coupon.coupon.discount_value | currency }})
                                                                    </span>
                                                                </td>
                                                                <td class="border p-2 text-right">
                                                                    <ns-button @click="applyCoupon( coupon )" type="info">{{ __( 'Use Coupon' ) }}</ns-button>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </template>
                                    </ns-tabs-item>
                                    <ns-tabs-item identifier="rewards" :label="__( 'Rewards' )">
                                        <div class="flex-auto h-full justify-center flex items-center" v-if="isLoadingRewards">
                                            <ns-spinner size="36"></ns-spinner>
                                        </div>
                                        <template v-if="! isLoadingRewards">
                                            <div class="py-2 w-full">
                                                <h2 class="font-semibold text-primary">{{ __( 'Rewards' ) }}</h2>
                                            </div>
                                            <div class="flex-auto flex-col flex overflow-hidden">
                                                <div class="flex-auto overflow-y-auto">
                                                    <table class="table ns-table w-full">
                                                        <thead>
                                                            <tr class="text-primary">
                                                                <th width="150" class="p-2 border font-semibold">{{ __( 'Name' ) }}</th>
                                                                <th class="p-2 border font-semibold">{{ __( 'Points' ) }}</th>
                                                                <th class="p-2 border font-semibold">{{ __( 'Target' ) }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="text-primary text-sm" v-if="rewardsResponse.data">
                                                            <tr v-if="rewardsResponse.data.length === 0">
                                                                <td class="border p-2 text-center" colspan="4">{{ __( 'No rewards available the selected customer...' ) }}</td>
                                                            </tr>
                                                            <tr v-for="reward of rewardsResponse.data" :key="reward.id">
                                                                <td width="300" class="border p-2">
                                                                    <h3 class="text-center">{{ reward.reward_name }}</h3>
                                                                </td>
                                                                <td width="300" class="border p-2">
                                                                    <h3 class="text-center">{{ reward.points }}</h3>
                                                                </td>
                                                                <td width="300" class="border p-2">
                                                                    <h3 class="text-center">{{ reward.target }}</h3>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="py-1 flex justify-end">
                                                <ns-paginate :pagination="rewardsResponse" @load="loadRewards( $event )"></ns-paginate>
                                            </div>
                                        </template>
                                    </ns-tabs-item>
                                </ns-tabs>
                            </div>
                        </div>
                        <div class="p-2 border-t border-box-edge flex justify-between">
                            <div></div>
                            <div>
                                <ns-button @click="newTransaction( customer )" type="info">{{ __( 'Account Transaction' ) }}</ns-button>
                            </div>
                        </div>
                    </div>
                </ns-tabs-item>
            </ns-tabs>
        </div>
    </div>
</template>
<script>

import closeWithOverlayClicked from "@/libraries/popup-closer";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { Popup } from '@/libraries/popup';
import nsPosCustomerSelectPopupVue from './ns-pos-customer-select-popup.vue';
import nsCustomersTransactionPopupVue from './ns-customers-transaction-popup.vue';
import { __ } from '@/libraries/lang';
import nsPosCouponsLoadPopupVue from './ns-pos-coupons-load-popup.vue';
import nsPosConfirmPopupVue from './ns-pos-confirm-popup.vue';
import popupResolver from '@/libraries/popup-resolver';
import popupCloser from '@/libraries/popup-closer';
import nsPaginate from '@/components/ns-paginate.vue';
import nsOrderPreviewPopup from './ns-orders-preview-popup.vue';

export default {
    name: 'ns-pos-customers',
    data() {
        return {
            activeTab: 'create-customers',
            customer: null,
            subscription: null,
            orders: [],
            options: {},
            optionsSubscriber: null,
            selectedTab: 'orders',
            isLoadingCoupons: false,
            isLoadingRewards: false,
            coupons: [],
            rewardsResponse: [],
            order: null,
        }
    }, 
    components: {
        nsPaginate
    },
    destroyed() {
        this.subscription.unsubscribe();
        this.optionsSubscriber.unsubscribe();
    },
    mounted() {
        this.closeWithOverlayClicked();

        this.optionsSubscriber  =   POS.options.subscribe( options => {
            this.options    =   options;
        });

        this.subscription   =   POS.order.subscribe( order => {
            
            this.order  =   order;

            if ( this.$popupParams.customer !== undefined ) {
                this.activeTab  =   'account-payment';
                this.customer   =   this.$popupParams.customer;
                this.loadCustomerOrders( this.customer.id );
            } else if ( order.customer !== undefined ) {
                this.activeTab  =   'account-payment';
                this.customer   =   order.customer;
                this.loadCustomerOrders( this.customer.id );
            }
        });

        this.popupCloser();
    },
    methods: {
        __,

        reload() {
            this.loadCustomerOrders( this.customer.id );
        },

        popupResolver,
        popupCloser,

        getType( type ) {
            switch( type ) {
                case 'percentage_discount':
                    return __( 'Percentage Discount' );
                case 'flat_discount' :
                    return __( 'Flat Discount' );
            }
        },
        
        closeWithOverlayClicked,

        async openOrderOptions( order ) {
            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsOrderPreviewPopup, {
                        order,
                        resolve,
                        reject
                    });
                });

                this.reload();

            } catch( exception ) {
                nsSnackBar.error( __( 'An error occured while opening the order options' ) ).subscribe();
            }
        },

        doChangeTab( tab ) {
            this.selectedTab = tab;

            if ( tab === 'coupons' ) {
                this.loadCoupons();
            }

            if ( tab === 'rewards' ) {
                this.loadRewards();
            }
        },

        loadCoupons() {
            this.isLoadingCoupons   =   true;
            nsHttpClient.get( `/api/nexopos/v4/customers/${this.customer.id}/coupons` )
                .subscribe({
                    next: ( coupons ) => {
                        this.coupons            =   coupons;
                        this.isLoadingCoupons   =   false;
                    },
                    error: ( error ) => {
                        this.isLoadingCoupons   =   false;
                    }
                });
        },
        
        loadRewards( url = `/api/nexopos/v4/customers/${this.customer.id}/rewards` ) {
            this.isLoadingRewards   =   true;
            nsHttpClient.get( url )
                .subscribe({
                    next: ( rewardsResponse ) => {
                        this.rewardsResponse            =   rewardsResponse;
                        this.isLoadingRewards   =   false;
                    },
                    error: ( error ) => {
                        this.isLoadingRewards   =   false;
                    }
                });
        },

        prefillForm( event ) {
            if ( this.$popupParams.name !== undefined ) {
                event.main.value     =   this.$popupParams.name;
            }
        },

        openCustomerSelection() {
            this.$popup.close();
            Popup.show( nsPosCustomerSelectPopupVue );
        },

        loadCustomerOrders( customerID ) {
            nsHttpClient.get( `/api/nexopos/v4/customers/${customerID}/orders` )
                .subscribe( orders => {
                    this.orders     =   orders;
                });
        },

        newTransaction( customer ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsCustomersTransactionPopupVue, { customer, resolve, reject });
            });

            /**
             * let's update the customer
             * with upated reference.
             */
            promise.then( result => {
                POS.loadCustomer( customer.id )
                    .subscribe( _customer => {
                        POS.selectCustomer( _customer );
                    });
            })
        },

        applyCoupon( customerCoupon ) {
            if ( this.order.customer === undefined ) {
                Popup.show( nsPosConfirmPopupVue, {
                    title: __( 'Use Customer ?' ),
                    message: __( 'No customer is selected. Would you like to proceed with this customer ?' ),
                    onAction : ( action ) => {
                        if ( action ) {
                            POS.selectCustomer( this.customer )
                                .then( result => {
                                    this.proceedApplyingCoupon( customerCoupon );
                                })                            
                        }
                    }
                })
            } else if ( this.order.customer.id === this.customer.id ) {
                this.proceedApplyingCoupon( customerCoupon );
            } else if ( this.order.customer.id !== this.customer.id ) {
                Popup.show( nsPosConfirmPopupVue, {
                    title: __( 'Change Customer ?' ),
                    message: __( 'Would you like to assign this customer to the ongoing order ?' ),
                    onAction : ( action ) => {
                        if ( action ) {
                            POS.selectCustomer( this.customer )
                                .then( result => {
                                    this.proceedApplyingCoupon( customerCoupon );
                                })                            
                        }
                    }
                })
            }        
        },

        proceedApplyingCoupon( customerCoupon ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsPosCouponsLoadPopupVue, { apply_coupon : customerCoupon.code, resolve, reject })
            }).then( result => {
                this.popupResolver( false );
            }).catch( exception => {
                // ...
            })
        },

        handleSavedCustomer( response ) {
            nsSnackBar.success( response.message ).subscribe();
            POS.selectCustomer( response.entry );
            this.$popup.close();
        }
    }
}
</script>