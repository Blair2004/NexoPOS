<template>
    <div class="shadow ns-box">
        <div class="head p-2 ns-box-title flex justify-between border-b">
            <div class="-mx-2 flex flex-wrap">
                <div class="px-2">
                    <ns-button @click="printTable()" type="info">
                        <i class="las la-print"></i> 
                        <span>{{ __( 'Print' ) }}</span>
                    </ns-button>
                </div>
            </div>
        </div>
        <div class="body flex flex-col px-2" id="invoice-container">
            <div id="invoice-header" class="flex -mx-2 flex-wrap">
                <div class="w-full print:w-1/3 md:w-1/3 px-2">
                    <div class="p-2">
                        <h3 class="font-semibold text-xl text-primary border-b border-info-primary py-2">{{ __( 'Store Details' ) }}</h3>
                        <div class="details">
                            <ul class="my-1">
                                <li class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Order Code' ) }}</span>
                                    <span>{{ order.code }}</span>
                                </li>
                                <li class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Cashier' ) }}</span>
                                    <span>{{ order.user.username }}</span>
                                </li>
                                <li class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Date' ) }}</span>
                                    <span>{{ order.created_at }}</span>
                                </li>
                                <li class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Customer' ) }}</span>
                                    <span>{{ order.customer.name }}</span>
                                </li>
                                <li class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Type' ) }}</span>
                                    <span>{{ order.type }}</span>
                                </li>
                                <li class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Payment Status' ) }}</span>
                                    <span>{{ order.paymentStatus }}</span>
                                </li>
                                <li v-if="order.type === 'delivery'" class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ __( 'Delivery Status' ) }}</span>
                                    <span>{{ order.deliveryStatus }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="w-full print:w-1/3 md:w-1/3 px-2">
                    <div class="p-2">
                        <h3 class="font-semibold text-xl text-primary border-b border-info-primary py-2">{{ __( 'Billing Details' ) }}</h3>
                        <div class="details">
                            <ul class="my-1">
                                <li v-for="bill of billing" :key="bill.id" class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ bill.label }}</span>
                                    <span>{{ order.billing_address[ bill.name ] || 'N/A' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="w-full print:w-1/3 md:w-1/3 px-2">
                    <div class="p-2">
                        <h3 class="font-semibold text-xl text-primary border-b border-info-primary py-2">{{ __( 'Shipping Details' ) }}</h3>
                        <div class="details">
                            <ul class="my-1">
                                <li v-for="ship of shipping" :key="ship.id" class="flex justify-between text-secondary text-sm mb-1">
                                    <span class="font-semibold">{{ ship.label }}</span>
                                    <span>{{ order.shipping_address[ ship.name ] || 'N/A' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table w-full my-4">
                <table class="table ns-table w-full">
                    <thead class="text-secondary">
                        <tr>
                            <th width="400" class="p-2 border">{{ __( 'Product' ) }}</th>
                            <th width="200" class="p-2 border">{{ __( 'Unit Price' ) }}</th>
                            <th width="200" class="p-2 border">{{ __( 'Quantity' ) }}</th>
                            <th width="200" class="p-2 border">{{ __( 'Discount' ) }}</th>
                            <th width="200" class="p-2 border">{{ __( 'Tax' ) }}</th>
                            <th width="200" class="p-2 border">{{ __( 'Total Price' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product of order.products" :key="product.id">
                            <td class="p-2 border">
                                <h3 class="text-primary">{{ product.name }}</h3>
                                <span class="text-sm text-secondary">{{ product.unit }}</span>
                            </td>
                            <td class="p-2 border text-center text-primary">{{ nsCurrency( product.unit_price ) }}</td>
                            <td class="p-2 border text-center text-primary">{{ product.quantity }}</td>
                            <td class="p-2 border text-center text-primary">{{ nsCurrency( product.discount ) }}</td>
                            <td class="p-2 border text-center text-primary">{{ nsCurrency( product.tax_value ) }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( product.total_price ) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="font-semibold">
                        <tr>
                            <td class="p-2 border text-center text-primary" colspan="2">
                                <div class="flex justify-between" v-if="[ 'unpaid', 'partially_paid' ].includes( order.payment_status )">
                                    <span>
                                        {{ __( 'Expiration Date' ) }}
                                    </span>
                                    <span>{{ order.final_payment_date }}</span>
                                </div>
                            </td>
                            <td class="p-2 border text-center text-primary" colspan="2"></td>
                            <td class="p-2 border text-primary text-left">{{ __( 'Sub Total' ) }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( order.subtotal ) }}</td>
                        </tr>
                        <tr v-if="order.discount > 0">
                            <td class="p-2 border text-center text-primary" colspan="4"></td>
                            <td class="p-2 border text-primary text-left">{{ __( 'Discount' ) }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( - order.discount ) }}</td>
                        </tr>
                        <tr v-if="order.total_coupons > 0">
                            <td class="p-2 border text-center text-primary" colspan="4"></td>
                            <td class="p-2 border text-left text-primary">{{ __( 'Coupons' ) }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( - order.total_coupons ) }}</td>
                        </tr>
                        <tr v-if="order.shipping > 0">
                            <td class="p-2 border text-center text-primary" colspan="4"></td>
                            <td class="p-2 border text-primary text-left">{{ __( 'Shipping' ) }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( order.shipping ) }}</td>
                        </tr>
                        <tr :key="tax.id" v-for="tax of order.taxes">
                            <td class="p-2 border text-center text-primary" colspan="4"></td>
                            <td class="p-2 border text-primary text-left">{{ tax.tax_name }} &mdash; {{ order.tax_type === 'inclusive' ? __( 'Inclusive' ) : __( 'Exclusive' )  }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( order.tax_value ) }}</td>
                        </tr>
                        <tr :key="tax.id" v-for="tax of order.taxes">
                            <td class="p-2 border text-center text-primary" colspan="4"></td>
                            <td class="p-2 border text-primary text-left">{{ tax.tax_name }} &mdash; {{ order.tax_type === 'inclusive' ? __( 'Inclusive' ) : __( 'Exclusive' )  }}</td>
                            <td class="p-2 border text-right text-primary">{{ tax.tax_value | currency }}</td>
                        </tr>
                        <tr>
                            <td class="p-2 border text-center text-primary" colspan="4"></td>
                            <td class="p-2 border text-primary text-left">{{ __( 'Total' ) }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( order.total ) }}</td>
                        </tr>
                        <tr>
                            <td class="p-2 border text-center text-primary" colspan="4"></td>
                            <td class="p-2 border text-primary text-left">{{ __( 'Paid' ) }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( order.tendered ) }}</td>
                        </tr>
                        <tr v-if="[ 'partially_paid', 'unpaid' ].includes( order.payment_status )" class="error">
                            <td class="p-2 border text-center" colspan="4"></td>
                            <td class="p-2 border text-left">{{ __( 'Due' ) }}</td>
                            <td class="p-2 border text-right">{{ nsCurrency( order.change ) }}</td>
                        </tr>
                        <tr v-else>
                            <td class="p-2 border text-center text-primary" colspan="4"></td>
                            <td class="p-2 border text-primary text-left">{{ __( 'Change' ) }}</td>
                            <td class="p-2 border text-right text-primary">{{ nsCurrency( order.change ) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>
<script>
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';

export default {
    props: [ 'order', 'billing', 'shipping' ],
    methods: {
        __,
        nsCurrency,
        printTable() {
            this.$htmlToPaper( 'invoice-container' )
        }
    }
}
</script>