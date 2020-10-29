<template>
    <div class="shadow-lg bg-white w-6/7-screen md:w-3/5-screen lg:w-2/5-screen h-6/7-screen flex flex-col overflow-hidden">
        <div class="p-2 flex justify-between text-gray-700 items-center border-b">
            <h3 class="font-semibold">Orders</h3>
            <div>
                <ns-close-button @click="$popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 flex overflow-hidden flex-auto">
            <ns-tabs :active="active" @changeTab="setActiveTab( $event )">
                <ns-tabs-item identifier="hold" label="On Hold" padding="p-0" class="flex flex-col overflow-hidden">
                    <div class="flex flex-col overflow-hidden">
                        <div class="p-1">
                            <div class="flex rounded border-2 border-blue-400">
                                <input @keyup.enter="searchOrder()" v-model="searchField" type="text" class="p-2 outline-none flex-auto">
                                <button @click="searchOrder()" class="w-16 md:w-24 bg-blue-400 text-white">
                                    <i class="las la-search"></i>
                                    <span class="mr-1 hidden md:visible">Search</span>
                                </button>
                            </div>
                        </div>
                        <div class="overflow-y-auto">
                            <div class="flex p-2 flex-col overflow-y-auto">
                                <div class="border-b border-blue-400 w-full py-2" v-for="order of orders" :key="order.id">
                                    <h3 class="text-gray-700">{{ order.title || 'Untitled Order' }}</h3>
                                    <div class="px-2">
                                        <div class="flex flex-wrap -mx-4">
                                            <div class="w-full md:w-1/2 px-2">
                                                <p class="text-sm text-gray-600"><strong>Cashier</strong> : {{ order.nexopos_users_username }}</p>
                                                <p class="text-sm text-gray-600"><strong>Register</strong> : {{ order.total | currency }}</p>
                                                <p class="text-sm text-gray-600"><strong>Tendered</strong> : {{ order.tendered | currency }}</p>
                                            </div>
                                            <div class="w-full md:w-1/2 px-2">
                                                <p class="text-sm text-gray-600"><strong>Customer</strong> : {{ order.nexopos_customers_name }}</p>
                                                <p class="text-sm text-gray-600"><strong>Date</strong> : {{ order.created_at }}</p>
                                                <p class="text-sm text-gray-600"><strong>Type</strong> : {{ order.type }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end w-full mt-2">
                                        <div class="flex rounded-lg overflow-hidden">
                                            <button @click="proceedOpenOrder( order )" class="text-white bg-green-400 outline-none px-2 py-1"><i class="las la-lock-open"></i> Open</button>
                                            <button @click="previewOrder( order )" class="text-white bg-blue-400 outline-none px-2 py-1"><i class="las la-eye"></i> Products</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ns-tabs-item>
                <ns-tabs-item identifier="unpaid" label="Unpaid">

                </ns-tabs-item>
                <ns-tabs-item identifier="partially-paid" label="Partially Paid">

                </ns-tabs-item>
            </ns-tabs>
        </div>
        <div class="p-2 flex justify-between border-t bg-gray-200">
            <div></div>
            <div>
                <ns-button>close</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import { nsEvent, nsHttpClient } from '@/bootstrap';
import nsPosConfirmPopupVue from './ns-pos-confirm-popup.vue';
import nsPosOrderProductsPopupVue from './ns-pos-order-products-popup.vue';
export default {
    methods: {
        searchOrder() {
            nsHttpClient.post( '/api/nexopos/v4/orders/search', {
                    search: this.searchField
                })
                .subscribe( orders => {
                    this.orders     =   orders;
                })
        },

        setActiveTab( event ) {
            this.active     =   event;
        },

        openOrder( order ) {
            POS.loadOrder( order.id );
            this.$popup.close();
        },

        loadOrderFromType( type ) {
            nsHttpClient.get( '/api/nexopos/v4/crud/ns.hold-orders' )
                .subscribe( result => {
                    this.orders     =   result.data;
                });
        },
        previewOrder( order ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsPosOrderProductsPopupVue, { order, resolve, reject });
            });

            promise.then( products => {
                this.proceedOpenOrder( order );
            });
        },
        proceedOpenOrder( order ) {
            const products  =   POS.products.getValue();

            if ( products.length > 0 ) {
                return Popup.show( nsPosConfirmPopupVue, {
                    title: 'Confirm Your Action',
                    message: 'The cart is not empty. Opening an order will clear your cart would you proceed ?',
                    onAction: ( action ) => {
                        if ( action ) {
                            this.openOrder( order );
                        }
                    }
                })
            }

            this.openOrder( order );
        }
    },
    data() {
        return {
            active: 'hold',
            searchField: '',
            orders: [],
        }
    },
    mounted() {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                this.$popup.close();
            }
        });
        this.loadOrderFromType( this.active );
    }
}
</script>