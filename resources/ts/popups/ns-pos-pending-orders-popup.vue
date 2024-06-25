<template>
    <div class="shadow-lg ns-box w-6/7-screen md:w-3/5-screen lg:w-2/5-screen h-6/7-screen flex flex-col overflow-hidden">
        <div class="p-2 flex justify-between text-primary items-center ns-box-header border-b">
            <h3 class="font-semibold">{{ __( 'Orders' ) }}</h3>
            <div>
                <ns-close-button @click="popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 flex overflow-hidden flex-auto ns-box-body">
            <ns-tabs :active="active" @changeTab="setActiveTab( $event )">
                <ns-tabs-item identifier="ns.hold-orders" :label="__( 'On Hold' )" padding="p-0" class="flex flex-col overflow-hidden">
                    <ns-pos-pending-orders :orders="orders" 
                        @searchOrder="searchOrder( $event )"
                        @previewOrder="previewOrder( $event )"
                        @printOrder="printOrder( $event )"
                        @proceedOpenOrder="proceedOpenOrder( $event )">
                    </ns-pos-pending-orders>
                </ns-tabs-item>
                <ns-tabs-item identifier="ns.unpaid-orders" :label="__( 'Unpaid' )" padding="p-0" class="flex flex-col overflow-hidden">
                    <ns-pos-pending-orders :orders="orders" 
                        @searchOrder="searchOrder( $event )"
                        @previewOrder="previewOrder( $event )"
                        @printOrder="printOrder( $event )"
                        @proceedOpenOrder="proceedOpenOrder( $event )">
                    </ns-pos-pending-orders>
                </ns-tabs-item>
                <ns-tabs-item identifier="ns.partially-paid-orders" :label="__( 'Partially Paid' )" padding="p-0" class="flex flex-col overflow-hidden">
                    <ns-pos-pending-orders :orders="orders" 
                        @searchOrder="searchOrder( $event )"
                        @previewOrder="previewOrder( $event )"
                        @printOrder="printOrder( $event )"
                        @proceedOpenOrder="proceedOpenOrder( $event )">
                    </ns-pos-pending-orders>
                </ns-tabs-item>
            </ns-tabs>
        </div>
        <div class="p-2 flex justify-between ns-box-footer border-t">
            <div></div>
            <div>
                <ns-button @click="popup.close()" type="info">{{ __( 'Close' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import nsPosConfirmPopupVue from './ns-pos-confirm-popup.vue';
import nsPosOrderProductsPopupVue from './ns-pos-order-products-popup.vue';
import nsPosPendingOrders from './ns-pos-pending-orders.vue';
import { __ } from '~/libraries/lang';
import popupResolver from '~/libraries/popup-resolver';
import popupCloser from '~/libraries/popup-closer';

declare const POS, Popup, nsEvent, nsHttpClient;

export default {
    props: [ 'popup' ],
    components: {
        nsPosPendingOrders
    },
    methods: {
        __,
        popupResolver,
        popupCloser,
        
        searchOrder( search ) {
            nsHttpClient.get( `/api/crud/${this.active}?search=${search}` )
                .subscribe( (result) => {
                    this.orders     =   result.data;
                })
        },

        setActiveTab( event ) {
            this.active     =   event;
            this.loadOrderFromType( event );
        },

        openOrder( order ) {
            POS.loadOrder( order.id );
            this.popup.close();
        },

        loadOrderFromType( type ) {
            nsHttpClient.get( `/api/crud/${type}` )
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
            }, ( error ) => error );
        },
        printOrder( order ) {
            POS.print.process( order.id, 'sale' );
        },
        proceedOpenOrder( order ) {
            const products  =   POS.products.getValue();

            if ( products.length > 0 ) {
                return Popup.show( nsPosConfirmPopupVue, {
                    title: __( 'Confirm Your Action' ),
                    message: __( 'The cart is not empty. Opening an order will clear your cart would you proceed ?' ),
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
            active: 'ns.hold-orders',
            searchField: '',
            orders: [],
        }
    },
    mounted() {
        this.loadOrderFromType( this.active );
        this.popupCloser();
    }
}
</script>