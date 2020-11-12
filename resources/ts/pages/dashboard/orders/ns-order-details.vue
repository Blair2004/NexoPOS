<template>
    <div class="-mx-4 flex flex-wrap">
        <div class="px-4 w-full md:w-1/3 lg:w-1/4 mb-2">
            <h3 class="font-semibold text-gray-800 pb-2">Order Status</h3>
            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Customer</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ order.nexopos_customers_name }}</div>
            </div>
            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Type</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getTypeLabel( order.type ) }}</div>
            </div>
            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Delivery Status</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getDeliveryStatus( order.delivery_status ) }}</div>
            </div>
            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Proceessing Status</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getProcessingStatus( order.delivery_status ) }}</div>
            </div>
            <div class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Payment Status</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getPaymentStatus( order.payment_status ) }}</div>
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
            <div :key="product.id" v-for="product of order.products" class="border-b border-blue-400 p-2 flex justify-between items-start bg-gray-100">
                <div>
                    <h4 class="text-semibold text-gray-700">{{ product.name }} (x{{ product.quantity }})</h4>
                    <p class="text-gray-600 text-sm">{{ product.unit.name || 'N/A' }}</p>
                </div>
                <div class="font-semibold text-gray-800">{{ product.unit_price | currency }}</div>
            </div>
        </div>
    </div>
</template>
<script>
import Labels from "@/libraries/labels";

export default {
    props: [ 'order' ],
    data() {
        return {
            labels: new Labels,
        }
    },
    methods: {
    }
}
</script>