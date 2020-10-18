<script>
import { nsHttpClient } from "@/bootstrap";
import { nsCurrency } from "@/filters/currency";
import { forkJoin } from "rxjs";

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
            payments: []
        }
    },
    methods: {
        closePopup() {
            this.$popup.close();
        },
        setActive( active ) {
            this.active     =   active;
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
        getTypeLabel( label ) {
            switch( label ) {
                // @todo localization needed here
                case 'delivery' : return 'Delivery'; break;
                case 'takeaway' : return 'Take Away'; break;
                default : return 'Unknown Type'; break;
            }
        },
        getDeliveryStatus( label ) {
            switch( label ) {
                // @todo localization needed here
                case 'pending' : return 'Pending'; break;
                case 'ongoing' : return 'Ongoing'; break;
                case 'delivered' : return 'Delivered'; break;
                case 'failed' : return 'Delivery Failure'; break;
                default : return 'Unknown Status'; break;
            }
        },
        getProcessingStatus( label ) {
            switch( label ) {
                // @todo localization needed here
                case 'pending' : return 'Pending'; break;
                case 'ongoing' : return 'Ongoing'; break;
                case 'done' : return 'Done'; break;
                case 'failed' : return 'Failure'; break;
                default : return 'Unknown Status'; break;
            }
        },
        getPaymentStatus( label ) {
            switch( label ) {
                // @todo localization needed here
                case 'paid' : return 'Paid'; break;
                case 'unpaid' : return 'Unpaid'; break;
                case 'partially_paid' : return 'Partially Paid'; break;
                default : return 'Unknown Status'; break;
            }
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
        <div class="p-2 bg-gray-100 flex flex-auto">
            <ns-tabs :active="active" @active="setActive( $event )">
                <!-- Summary -->
                <ns-tabs-item label="Details" identifier="details">
                    <div class="-mx-4 flex flex-wrap">

                        <div class="px-4 w-full md:w-1/3 lg:w-1/4 mb-2">
                            <h3 class="font-semibold text-gray-800 pb-2">Order Status</h3>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">
                                        <span>Customer</span>
                                    </h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ order.customer.name }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">
                                        <span>Type</span>
                                    </h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ getTypeLabel( order.type ) }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">
                                        <span>Delivery Status</span>
                                    </h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ getDeliveryStatus( order.delivery_status ) }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">
                                        <span>Proceessing Status</span>
                                    </h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ getProcessingStatus( order.delivery_status ) }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">
                                        <span>Payment Status</span>
                                    </h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ getPaymentStatus( order.payment_status ) }}</div>
                            </div>
                        </div>

                        <div class="px-4 w-full md:w-1/3 lg:w-1/4 mb-2">
                            <h3 class="font-semibold text-gray-800 pb-2">Payment Summary</h3>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">Sub Total</h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ order.subtotal | currency }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">
                                        <span>Discount</span>
                                        <span class="ml-1" v-if="order.discount_type === 'percentage'">({{ order.discount_percentage }}%)</span>
                                        <span class="ml-1" v-if="order.discount_type === 'flat'">(Flat)</span>
                                    </h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ order.discount | currency }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">Shipping</h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ order.shipping | currency }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">Taxes</h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ order.tax_value | currency }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">Paid</h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ order.tendered | currency }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">Change</h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ order.change | currency }}</div>
                            </div>
                        </div>

                        <div class="px-4 w-full md:w-1/3 lg:w-1/4 mb-2">
                            <h3 class="font-semibold text-gray-800 pb-2">Products</h3>
                            <div :key="product.id" v-for="product of products" class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">{{ product.name }} (x{{ product.quantity }})</h4>
                                    <p class="text-gray-600 text-sm">{{ product.unit.name || 'N/A' }}</p>
                                </div>
                                <div class="font-semibold text-gray-800">{{ product.unit_price | currency }}</div>
                            </div>
                        </div>
                    </div>
                </ns-tabs-item>

                <!-- End Summary -->

                <!-- Refund -->
                <ns-tabs-item label="Shipping Management" identifier="shipping">
                    <h1>Shipping Component</h1>
                </ns-tabs-item>
                <!-- End Refund -->

                <!-- Refund -->
                <ns-tabs-item label="Payments" identifier="payments">
                    <h1>Payment component</h1>
                </ns-tabs-item>
                <!-- End Refund -->

                <!-- Refund -->
                <ns-tabs-item label="Refund & Return" identifier="refund">
                    <h1>Refund component</h1>
                </ns-tabs-item>
                <!-- End Refund -->
            </ns-tabs>
        </div> 
        <div class="p-2 flex justify-between border-t border-gray-200">
            <div></div>
            <div>
                <ns-button @click="printOrder()" type="info">
                    <i class="las la-print"></i>
                    Print
                </ns-button>
            </div>
        </div>
    </div>
</template>