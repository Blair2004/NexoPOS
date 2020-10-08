<script>
import { nsHttpClient } from "@/bootstrap";
import { forkJoin } from "rxjs";

const nsOrderPreviewPopup   =   {
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
                <h3>Order Settings</h3>
            </div>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 bg-gray-100 flex flex-auto">
            <ns-tabs :active="active" @active="setActive( $event )">
                <ns-tabs-item label="Details" identifier="details">
                    <div class="-mx-4 flex flex-wrap">
                        <div class="px-4 w-full md:w-1/3 lg:w-1/4">
                            <h3 class="font-semibold text-gray-800 pb-2">Summary</h3>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">Sub Total</h4>
                                </div>
                                <div class="font-semibold text-gray-800">{{ order.subtotal | currency }}</div>
                            </div>
                            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">Discount</h4>
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
                        </div>
                        <div class="px-4 w-full md:w-1/3 lg:w-1/4">
                            <h3 class="font-semibold text-gray-800 pb-2">Products</h3>
                            <div :key="product.id" v-for="product of products" class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                                <div>
                                    <h4 class="text-semibold text-gray-700">{{ product.name }} (x{{ product.quantity }})</h4>
                                    <p class="text-gray-600 text-sm">{{ product.unit.name | 'N/A' }}</p>
                                </div>
                                <div class="font-semibold text-gray-800">{{ product.sale_price | currency }}</div>
                            </div>
                        </div>
                    </div>
                </ns-tabs-item>
                <ns-tabs-item label="Refunds" identifier="refunds">
                    <h1>Refund component</h1>
                </ns-tabs-item>
            </ns-tabs>
        </div> 
    </div>
</template>