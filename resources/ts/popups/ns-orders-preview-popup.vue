<script>
import { nsHttpClient, nsSnackBar } from "~/bootstrap";
import { nsCurrency } from "~/filters/currency";
import { forkJoin } from "rxjs";
import nsOrderRefund from "~/pages/dashboard/orders/ns-order-refund.vue";
import nsPromptPopupVue from "./ns-prompt-popup.vue";
import nsPosConfirmPopupVue from "./ns-pos-confirm-popup.vue";
import nsOrderPayment from "~/pages/dashboard/orders/ns-order-payment.vue";
import nsOrderDetails from "~/pages/dashboard/orders/ns-order-details.vue";
import nsOrderInstalments from "~/pages/dashboard/orders/ns-order-instalments.vue";
import { __ } from "~/libraries/lang";
import popupResolver from "~/libraries/popup-resolver";
import popupCloser from "~/libraries/popup-closer";
import Print from "~/libraries/print";

export default {
    name: 'ns-orders-preview-popup',
    props: [ 'popup' ],
    data() {
        return {
            active: 'details',
            order: new Object,
            rawOrder: new Object,
            products: [],
            payments: [],
            options: null,
            print: new Print({ urls: systemUrls, options: systemOptions }),
            settings: null,
            orderDetailLoaded: false,
        }
    },
    components: {
        nsOrderRefund,
        nsOrderPayment,
        nsOrderDetails,
        nsOrderInstalments,
    },
    computed: {
        isVoidable() {
            return [ 'paid', 'partially_paid', 'unpaid' ].includes( this.order.payment_status );
        },
        isDeleteAble() {
            return [ 'hold' ].includes( this.order.payment_status );
        },
    },
    methods: {
        __,
        popupResolver,
        popupCloser,
        nsCurrency,

        closePopup( action = false ) {
            this.popupResolver( action );
        },
        setActive( active ) {
            this.active     =   active;
        },
        printOrder() {
            this.print.process( this.order.id, 'sale' );
        },
        refresh() {
            /**
             * Withn the popup let's refresh the order
             * and display updated values.
             */
            this.loadOrderDetails( this.order.id );
        },

        loadOrderDetails( orderId ) {
            this.orderDetailLoaded  =   false;
            forkJoin([
                nsHttpClient.get( `/api/orders/${orderId}` ),
                nsHttpClient.get( `/api/orders/${orderId}/products` ),
                nsHttpClient.get( `/api/orders/${orderId}/payments` ),
            ])
                .subscribe( result => {
                    this.orderDetailLoaded  =   true;
                    this.order              =   result[0];
                    this.products           =   result[1];
                    this.payments           =   result[2];
                });
        },
        deleteOrder() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to delete this order' ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.delete( `/api/orders/${this.order.id}` )
                            .subscribe({
                                next: result => {
                                    nsSnackBar.success( result.message ).subscribe();
                                    this.refreshCrudTable();
                                    this.closePopup(true);
                                },
                                error:  error => {
                                    nsSnackBar.error( error.message ).subscribe();
                                }
                            })
                    }
                }
            })
        },
        voidOrder() {
            try {
                const result  =   new Promise( ( resolve, reject ) => {
                    Popup.show( nsPromptPopupVue, {
                        resolve,
                        reject,
                        title: __( 'Confirm Your Action' ),
                        message: __( 'The current order will be void. This action will be recorded. Consider providing a reason for this operation' ),
                        onAction:  ( reason ) => {
                            if ( reason !== false ) {
                                nsHttpClient.post( `/api/orders/${this.order.id}/void`, { reason })
                                    .subscribe({
                                        next: result => {
                                            nsSnackBar.success( result.message ).subscribe();
                                            this.refreshCrudTable();
                                            this.closePopup(true);
                                        },
                                        error:  error => {
                                            nsSnackBar.error( error.message ).subscribe();
                                        }
                                    })
                            }
                        }
                    });
                })
            } catch( exception ) {
                // ...
                console.log( exception );
            }
        },
        refreshCrudTable() {
            this.popup.params.component.$emit( 'updated', true );
        }
    },
    watch: {
        active() {
            if ( this.active === 'details' ) {
                this.loadOrderDetails( this.rawOrder.id );
            }
        }
    },
    mounted() {
        this.rawOrder   =   this.popup.params.order;
        this.options    =   systemOptions;
        this.urls       =   systemUrls;
        this.loadOrderDetails( this.rawOrder.id );
        this.popupCloser();
    }
}
</script>
<template>
    <div class="h-95vh w-95vw md:h-6/7-screen md:w-6/7-screen overflow-hidden shadow-xl ns-box flex flex-col">
        <div class="border-b ns-box-header p-3 flex items-center justify-between">
            <div>
                <h3>{{ __( 'Order Options' ) }}</h3>
            </div>
            <div>
                <ns-close-button @click="closePopup(true)"></ns-close-button>
            </div>
        </div>
        <div class="p-2 ns-box-body overflow-hidden flex flex-auto">
            <ns-tabs v-if="order.id" :active="active" @active="setActive( $event )">
                <!-- Summary -->
                <ns-tabs-item :label="__( 'Details' )" identifier="details" class="overflow-y-auto">
                    <ns-order-details v-if="order" :order="order"></ns-order-details>
                </ns-tabs-item>

                <!-- End Summary -->

                <!-- Payment Component -->
                <ns-tabs-item :visible="! [ 'order_void', 'hold', 'refunded', 'partially_refunded' ].includes( order.payment_status )" :label="__( 'Payments' )" identifier="payments">
                    <ns-order-payment v-if="order" @changed="refresh()" :order="order"></ns-order-payment>
                </ns-tabs-item>
                <!-- End Refund -->

                <!-- Refund -->
                <ns-tabs-item :visible="! [ 'order_void', 'hold', 'refunded' ].includes( order.payment_status )" :label="__( 'Refund & Return' )" identifier="refund">
                    <ns-order-refund v-if="order" @loadTab="setActive( $event )" @changed="refresh()" :order="order"></ns-order-refund>
                </ns-tabs-item>
                <!-- End Refund -->

                <!-- Instalment -->
                <ns-tabs-item :visible="[ 'partially_paid', 'unpaid' ].includes( order.payment_status ) && order.support_instalments" :label="__( 'Installments' )" identifier="instalments">
                    <ns-order-instalments v-if="order" @changed="refresh()" :order="order"></ns-order-instalments>
                </ns-tabs-item>
                <!-- End Instalment -->
            </ns-tabs>
            <div v-if="! order.id" class="h-full w-full flex items-center justify-center">
                <ns-spinner></ns-spinner>
            </div>
        </div> 
        <div v-if="orderDetailLoaded" class="p-2 flex justify-between border-t ns-box-footer">
            <div>
                <ns-button v-if="isVoidable" @click="voidOrder()" type="error">
                    <i class="las la-ban"></i>
                    {{ __( 'Void' ) }}
                </ns-button>
                <ns-button v-if="isDeleteAble" @click="deleteOrder()" type="error">
                    <i class="las la-trash"></i>
                    {{ __( 'Delete' ) }}
                </ns-button>
            </div>
            <div>
                <ns-button @click="printOrder()" type="info">
                    <i class="las la-print"></i>
                    {{ __( 'Print' ) }}
                </ns-button>
            </div>
        </div>
    </div>
</template>