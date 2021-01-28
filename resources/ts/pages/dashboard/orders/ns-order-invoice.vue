<template>
    <div class="shadow bg-white">
        <div class="head p-2 bg-gray-100 flex justify-between border-b border-gray-300">
            <div class="-mx-2 flex flex-wrap">
                <div class="px-2">
                    <ns-button @click="printTable()" type="info">
                        <i class="las la-print"></i> 
                        <span>Print</span>
                    </ns-button>
                </div>
            </div>
        </div>
        <div class="body flex flex-col px-2" id="invoice-container">
            <div id="invoice-header" class="flex -mx-2 flex-wrap">
                <div class="w-full print:w-1/3 md:w-1/3 px-2">
                    <div class="p-2">
                        <h3 class="font-semibold text-xl text-gray-700 border-b border-blue-400 py-2">{{ __( 'Store Details' ) }}</h3>
                        <div class="details">
                            <ul class="my-1">
                                <li class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Order Code' ) }}</span>
                                    <span>{{ order.code }}</span>
                                </li>
                                <li class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Cashier' ) }}</span>
                                    <span>{{ order.user.username }}</span>
                                </li>
                                <li class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Date' ) }}</span>
                                    <span>{{ order.created_at }}</span>
                                </li>
                                <li class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Customer' ) }}</span>
                                    <span>{{ order.customer.name }}</span>
                                </li>
                                <li class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Type' ) }}</span>
                                    <span>{{ order.type }}</span>
                                </li>
                                <li class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Payment Status' ) }}</span>
                                    <span>{{ order.payment_status }}</span>
                                </li>
                                <li v-if="order.type === 'delivery'" class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Delivery Status' ) }}</span>
                                    <span>{{ order.delivery_status }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="w-full print:w-1/3 md:w-1/3 px-2">
                    <div class="p-2">
                        <h3 class="font-semibold text-xl text-gray-700 border-b border-blue-400 py-2">{{ __( 'Billing Details' ) }}</h3>
                        <div class="details">
                            <ul class="my-1">
                                <li v-for="bill of billing" :key="bill.id" class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ bill.label }}</span>
                                    <span>{{ order.billing_address[ bill.name ] || 'N/A' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="w-full print:w-1/3 md:w-1/3 px-2">
                    <div class="p-2">
                        <h3 class="font-semibold text-xl text-gray-700 border-b border-blue-400 py-2">{{ __( 'Shipping Details' ) }}</h3>
                        <div class="details">
                            <ul class="my-1">
                                <li v-for="ship of shipping" :key="ship.id" class="flex justify-between text-gray-600 text-sm mb-1">
                                    <span class="font-semibold">{{ ship.label }}</span>
                                    <span>{{ order.shipping_address[ ship.name ] || 'N/A' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table w-full my-4">
                <table class="table w-full">
                    <thead class="text-gray-600 bg-gray-100">
                        <tr>
                            <th width="400" class="p-2 border border-gray-200">{{ __( 'Product' ) }}</th>
                            <th width="200" class="p-2 border border-gray-200">{{ __( 'Unit Price' ) }}</th>
                            <th width="200" class="p-2 border border-gray-200">{{ __( 'Quantity' ) }}</th>
                            <th width="200" class="p-2 border border-gray-200">{{ __( 'Discount' ) }}</th>
                            <th width="200" class="p-2 border border-gray-200">{{ __( 'Tax' ) }}</th>
                            <th width="200" class="p-2 border border-gray-200">{{ __( 'Total Price' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product of order.products" :key="product.id">
                            <td class="p-2 border border-gray-200">
                                <h3 class="text-gray-700">{{ product.name }}</h3>
                                <span class="text-sm text-gray-600">{{ product.unit }}</span>
                            </td>
                            <td class="p-2 border border-gray-200 text-center text-gray-700">{{ product.unit_price | currency }}</td>
                            <td class="p-2 border border-gray-200 text-center text-gray-700">{{ product.quantity }}</td>
                            <td class="p-2 border border-gray-200 text-center text-gray-700">{{ product.discount | currency }}</td>
                            <td class="p-2 border border-gray-200 text-center text-gray-700">{{ product.tax_value | currency }}</td>
                            <td class="p-2 border border-gray-200 text-right text-gray-700">{{ product.total_price | currency }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="font-semibold bg-gray-100">
                        <tr>
                            <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="2">
                                <div class="flex justify-between" v-if="[ 'unpaid', 'partially_paid' ].includes( order.payment_status )">
                                    <span>
                                        {{ __( 'Expiration Date' ) }}
                                    </span>
                                    <span>{{ order.expected_payment_date }}</span>
                                </div>
                            </td>
                            <td class="p-2 border border-gray-200 text-center text-gray-700">{{ __( 'Coupons' ) }}</td>
                            <td class="p-2 border border-gray-200 text-center text-gray-700">{{ order.total_coupons | currency }}</td>
                            <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Sub Total' ) }}</td>
                            <td class="p-2 border border-gray-200 text-right text-gray-700">{{ order.subtotal | currency }}</td>
                        </tr>
                        <tr>
                            <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                            <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Discount' ) }}</td>
                            <td class="p-2 border border-gray-200 text-right text-gray-700">{{ order.discount | currency }}</td>
                        </tr>
                        <tr>
                            <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                            <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Shipping' ) }}</td>
                            <td class="p-2 border border-gray-200 text-right text-gray-700">{{ order.shipping | currency }}</td>
                        </tr>
                        <tr>
                            <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                            <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Total' ) }}</td>
                            <td class="p-2 border border-gray-200 text-right text-gray-700">{{ order.total | currency }}</td>
                        </tr>
                        <tr>
                            <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                            <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Paid' ) }}</td>
                            <td class="p-2 border border-gray-200 text-right text-gray-700">{{ order.tendered | currency }}</td>
                        </tr>
                        <tr v-if="[ 'partially_paid', 'unpaid' ].includes( order.payment_status )" class="bg-red-200 border-red-300">
                            <td class="p-2 border border-red-200 text-center text-red-700" colspan="4"></td>
                            <td class="p-2 border border-red-200 text-red-700 text-left">{{ __( 'Due' ) }}</td>
                            <td class="p-2 border border-red-200 text-right text-red-700">{{ order.change | currency }}</td>
                        </tr>
                        <tr v-else>
                            <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                            <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Change' ) }}</td>
                            <td class="p-2 border border-gray-200 text-right text-gray-700">{{ order.change | currency }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang'
export default {
    props: [ 'order', 'billing', 'shipping' ],
    methods: {
        __,
        printTable() {
            this.$htmlToPaper( 'invoice-container' )
        }
    }
}
</script>