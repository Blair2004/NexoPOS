<script>
import { nsHttpClient, nsSnackBar } from "@/bootstrap";
import { nsCurrency } from "@/filters/currency";
import { forkJoin } from "rxjs";
import nsOrderRefund from "@/pages/dashboard/orders/ns-order-refund.vue";
import nsPromptPopupVue from "./ns-prompt-popup.vue";
import nsPosConfirmPopupVue from "./ns-pos-confirm-popup.vue";
import nsOrderPayment from "@/pages/dashboard/orders/ns-order-payment.vue";
import nsOrderDetails from "@/pages/dashboard/orders/ns-order-details.vue";
import nsOrderInstalments from "@/pages/dashboard/orders/ns-order-instalments.vue";

/**
 * @var {ExtendedVue}
 */
const nsOrderPreviewPopup   =   {
    filters: {
        nsCurrency
    },
    name: 'ns-preview-popup',
    data() {
        return {
            active: 'details',
            order: new Object,
            products: [],
            payments: [],
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
        closePopup() {
            this.$popup.close();
        },
        setActive( active ) {
            this.active     =   active;
        },
        refresh() {
            /**
             * this will notify the crud component to
             * refresh the list of result as a row state has changed.
             */
            this.$popupParams.component.$emit( 'updated' );

            /**
             * Withn the popup let's refresh the order
             * and display updated values.
             */
            this.loadOrderDetails( this.$popupParams.order.id );
        },
        printOrder() {
            const order     =   this.$popupParams.order;

            nsHttpClient.get( `/api/nexopos/v4/orders/${order.id}/print/receipt` )
                .subscribe( result => {
                    nsSnackBar.success( result.message ).subscribe();
                });
        },
        loadOrderDetails( orderId ) {
            forkJoin([
                nsHttpClient.get( `/api/nexopos/v4/orders/${orderId}` ),
                nsHttpClient.get( `/api/nexopos/v4/orders/${orderId}/products` ),
                nsHttpClient.get( `/api/nexopos/v4/orders/${orderId}/payments` ),
            ])
                .subscribe( result => {
                    this.order      =   result[0];
                    this.products   =   result[1];
                    this.payments   =   result[2];
                });
        },
        deleteOrder() {
            Popup.show( nsPosConfirmPopupVue, {
                title: 'Confirm Your Action',
                message: 'Would you like to delete this order',
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.delete( `/api/nexopos/v4/orders/${this.$popupParams.order.id}` )
                            .subscribe( result => {
                                nsSnackBar.success( result.message ).subscribe();
                                this.refreshCrudTable();
                                this.closePopup();
                            }, error => {
                                nsSnackBar.error( error.message ).subscribe();
                            })
                    }
                }
            })
        },
        voidOrder() {
            Popup.show( nsPromptPopupVue, {
                title: 'Confirm Your Action',
                message: 'The current order will be void. This action will be recorded. Consider providing a reason for this operation',
                onAction:  ( reason ) => {
                    if ( reason !== false ) {
                        nsHttpClient.post( `/api/nexopos/v4/orders/${this.$popupParams.order.id}/void`, { reason })
                            .subscribe( result => {
                                nsSnackBar.success( result.message ).subscribe();
                                this.refreshCrudTable();
                                this.closePopup();
                            }, error => {
                                nsSnackBar.error( error.message ).subscribe();
                            })
                    }
                }
            });
        },
        refreshCrudTable() {
            this.$popupParams.component.$emit( 'updated', true );
        }
    },
    watch: {
        active() {
            if ( this.active === 'details' ) {
                this.loadOrderDetails( this.$popupParams.order.id );
            }
        }
    },
    mounted() {
        this.loadOrderDetails( this.$popupParams.order.id );
        
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                this.$popup.close();
            }
        })
    }
}

/**
 * in order to make sure the popup
 * is available globally.
 */
window.nsOrderPreviewPopup      =   nsOrderPreviewPopup;

export default nsOrderPreviewPopup;
</script>
<template>
    <div class="h-6/7-screen w-6/7-screen shadow-xl bg-white flex flex-col">
        <div class="border-b border-gray-300 p-3 flex items-center justify-between">
            <div>
                <h3>Order Options</h3>
            </div>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 overflow-auto bg-gray-100 flex flex-auto">
            <ns-tabs v-if="order.id" :active="active" @active="setActive( $event )">
                <!-- Summary -->
                <ns-tabs-item label="Details" identifier="details" class="overflow-y-auto">
                    <ns-order-details :order="order"></ns-order-details>
                </ns-tabs-item>

                <!-- End Summary -->

                <!-- Payment Component -->
                <ns-tabs-item v-if="! [ 'order_void', 'hold', 'refunded', 'partially_refunded' ].includes( order.payment_status )" label="Payments" identifier="payments" class="overflow-y-auto">
                    <ns-order-payment @changed="refresh()" :order="order"></ns-order-payment>
                </ns-tabs-item>
                <!-- End Refund -->

                <!-- Refund -->
                <ns-tabs-item v-if="! [ 'order_void', 'hold', 'refunded' ].includes( order.payment_status )" label="Refund & Return" identifier="refund" class="flex overflow-y-auto">
                    <ns-order-refund @changed="refresh()" :order="order"></ns-order-refund>
                </ns-tabs-item>
                <!-- End Refund -->

                <!-- Instalment -->
                <ns-tabs-item v-if="[ 'partially_paid' ].includes( order.payment_status )" label="Instalments" identifier="instalments" class="flex overflow-y-auto">
                    <ns-order-instalments @changed="refresh()" :order="order"></ns-order-instalments>
                </ns-tabs-item>
                <!-- End Instalment -->
            </ns-tabs>
            <div v-if="! order.id" class="h-full w-full flex items-center justify-center">
                <ns-spinner></ns-spinner>
            </div>
        </div> 
        <div class="p-2 flex justify-between border-t border-gray-200">
            <div>
                <ns-button v-if="isVoidable" @click="voidOrder()" type="danger">
                    <i class="las la-ban"></i>
                    Void
                </ns-button>
                <ns-button v-if="isDeleteAble" @click="deleteOrder()" type="danger">
                    <i class="las la-trash"></i>
                    Delete
                </ns-button>
            </div>
            <div>
                <ns-button @click="printOrder()" type="info">
                    <i class="las la-print"></i>
                    Print
                </ns-button>
            </div>
        </div>
    </div>
</template>