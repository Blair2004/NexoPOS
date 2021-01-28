<template>
    <div class="-mx-4 flex flex-wrap">
        <div class="flex-auto">
            <div class="w-full mb-2 flex-wrap flex">
                <div class="w-full mb-2 px-4">
                    <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">Payment Summary</h3>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="bg-gray-200 p-2 flex justify-between items-start">
                        <div>
                            <h4 class="text-semibold text-gray-700">Sub Total</h4>
                        </div>
                        <div class="font-semibold text-gray-800">{{ order.subtotal | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-red-400 text-white">
                        <div>
                            <h4 class="text-semibold">
                                <span>Discount</span>
                                <span class="ml-1" v-if="order.discount_type === 'percentage'">({{ order.discount_percentage }}%)</span>
                                <span class="ml-1" v-if="order.discount_type === 'flat'">(Flat)</span>
                            </h4>
                        </div>
                        <div class="font-semibold">{{ order.discount | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-gray-100">
                        <div>
                            <h4 class="text-semibold text-gray-700">Shipping</h4>
                        </div>
                        <div class="font-semibold text-gray-800">{{ order.shipping | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-red-400 text-white">
                        <div>
                            <h4 class="text-semibold">
                                <span>Coupons</span>
                            </h4>
                        </div>
                        <div class="font-semibold">{{ order.total_coupons | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-blue-400 text-white">
                        <div>
                            <h4 class="text-semibold">Total</h4>
                        </div>
                        <div class="font-semibold">{{ order.total | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-yellow-400 text-gray-700">
                        <div>
                            <h4 class="text-semibold ">Taxes</h4>
                        </div>
                        <div class="font-semibold">{{ order.tax_value | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start text-gray-700 bg-gray-100">
                        <div>
                            <h4 class="text-semibold">Change</h4>
                        </div>
                        <div class="font-semibold">{{ order.change | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-teal-500 text-white">
                        <div>
                            <h4 class="text-semibold">Paid</h4>
                        </div>
                        <div class="font-semibold">{{ order.tendered | currency }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="px-4 w-full md:w-1/3 lg:w-1/4 mb-2">
            <div class="mb-2">
                <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">Order Status</h3>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Customer</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ order.nexopos_customers_name }}</div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Type</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getTypeLabel( order.type ) }}</div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Delivery Status</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getDeliveryStatus( order.delivery_status ) }}</div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Proceessing Status</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getProcessingStatus( order.delivery_status ) }}</div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>Payment Status</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getPaymentStatus( order.payment_status ) }}</div>
            </div>
        </div>

        <div class="px-4 w-full md:2/3 lg:w-3/4 mb-2">
            <div class="mb-2">
                <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">Products</h3>
            </div>
            <div :key="product.id" v-for="product of order.products" class="p-2 flex justify-between items-start bg-gray-200">
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