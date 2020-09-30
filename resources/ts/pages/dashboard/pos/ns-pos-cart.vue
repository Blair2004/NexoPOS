<template>
    <div id="pos-cart" class="rounded shadow bg-white flex-auto flex overflow-hidden">
        <div class="cart-table flex flex-auto flex-col overflow-hidden">
            <div class="w-full text-gray-700 font-semibold flex">
                <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200 bg-gray-100">Product</div>
                <div class="w-1/6 p-2 border-b border-t-0 border-gray-200 bg-gray-100">Quantity</div>
                <div class="w-1/6 p-2 border border-r-0 border-t-0 border-gray-200 bg-gray-100">Total</div>
            </div>
            <div class="flex flex-auto flex-col overflow-auto">
                
                <!-- Loop Procuts On Cart -->

                <div class="text-gray-700 flex" v-if="products.length === 0">
                    <div class="w-full text-center py-4 border-b border-gray-200">
                        <h3 class="text-gray-600">No products added...</h3>
                    </div>
                </div>

                <div :key="product.barcode" class="text-gray-700 flex" v-for="product of products">
                    <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200">
                        <h3 class="font-semibold">{{ product.name }} &mdash; {{ product.unit_name }}</h3>
                        <div class="flex justify-between">
                            <div class="-mx-1 flex">
                                <div class="px-1">
                                    <a
                                        :class="product.mode === 'wholesale' ? 'text-green-600 hover:text-green-700 border-green-600' : 'hover:text-blue-400 border-blue-400'"
                                        class="cursor-pointer outline-none border-dashed py-1 border-b  text-sm"
                                    >Price : {{ product.sale_price | currency }}</a>
                                </div>
                                <div class="px-1"> 
                                    <a @click="openDiscountPopup( product, 'product' )" class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Discount <span v-if="product.discount_type === 'percentage'">{{ product.discount_percentage }}%</span> : {{ product.discount_amount | currency }}</a>
                                </div>
                            </div>
                            <div class="-mx-1 flex">
                                <div class="px-1"> 
                                    <a @click="remove( product )" class="hover:text-red-400 cursor-pointer outline-none border-dashed py-1 border-b border-red-400 text-sm">
                                        <i class="las la-trash text-xl"></i>
                                    </a>
                                </div>
                                <div class="px-1"> 
                                    <a :class="product.mode === 'wholesale' ? 'text-green-600 border-green-600' : 'border-blue-400'" @click="toggleMode( product )" class="hover:text-blue-600 cursor-pointer outline-none border-dashed py-1 border-b  text-sm">
                                        <i class="las la-award text-xl"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div @click="changeQuantity( product )" class="w-1/6 p-2 border-b border-gray-200 flex items-center justify-center cursor-pointer hover:bg-blue-100">
                        <span class="border-b border-dashed border-blue-400 p-2">{{ product.quantity }}</span>
                    </div>
                    <div class="w-1/6 p-2 border border-r-0 border-t-0 border-gray-200 flex items-center justify-center">{{ product.total_price | currency }}</div>
                </div>
                
                <!-- End Loop -->

            </div>
            <div class="flex">
                <table class="table w-full text-sm text-gray-700">
                    <tr>
                        <td width="100" colspan="2" class="border border-gray-400 p-2">
                            <a @click="selectCustomer()" class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Customer : {{ customerName }}</a>
                        </td>
                        <td width="200" class="border border-gray-400 p-2">Sub Total</td>
                        <td width="200" class="border border-gray-400 p-2 text-right">{{ order.subtotal | currency }}</td>
                    </tr>
                    <tr>
                        <td width="100" colspan="2" class="border border-gray-400 p-2">
                            <a @click="openOrderType()" class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Type : {{ selectedType }}</a>
                        </td>
                        <td width="200" class="border border-gray-400 p-2">
                            <span>Discount</span>
                            <span v-if="order.discount_type === 'percentage'">({{ order.discount_percentage }}%)</span>
                            <span v-if="order.discount_type === 'flat'">(Flat)</span>
                        </td>
                        <td width="200" class="border border-gray-400 p-2 text-right">
                            <a @click="openDiscountPopup( order, 'cart' )" class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">{{ order.discount_amount | currency }}</a>
                        </td>
                    </tr>
                    <tr v-if="order.type && order.type.identifier === 'delivery'">
                        <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                        <td width="200" class="border border-gray-400 p-2">Shipping</td>
                        <td width="200" class="border border-gray-400 p-2"></td>
                    </tr>
                    <tr class="bg-green-200">
                        <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                        <td width="200" class="border border-gray-400 p-2">Total</td>
                        <td width="200" class="border border-gray-400 p-2 text-right">{{ order.total | currency }}</td>
                    </tr>
                </table>
            </div>
            <div class="h-16 flex flex-shrink-0 border-t border-gray-200">
                <div @click="payOrder()" class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-green-500 text-white hover:bg-green-600 border-r border-green-600 flex-auto">
                    <i class="mr-2 text-3xl las la-cash-register"></i> 
                    <span class="text-2xl">Pay</span>
                </div>
                <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-blue-500 text-white border-r hover:bg-blue-600 border-blue-600 flex-auto">
                    <i class="mr-2 text-3xl las la-pause"></i> 
                    <span class="text-2xl">Hold</span>
                </div>
                <div @click="openDiscountPopup( order, 'cart' )" class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-white border-r border-gray-200 hover:bg-indigo-100 flex-auto text-gray-700">
                    <i class="mr-2 text-3xl las la-percent"></i> 
                    <span class="text-2xl">Discount</span>
                </div>
                <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-red-500 text-white border-gray-200 hover:bg-red-600 flex-auto">
                    <i class="mr-2 text-3xl las la-trash"></i> 
                    <span class="text-2xl">Void</span>
                </div>
            </div>
        </div>
    </div>
</template>
<script>

import { Popup } from '../../../libraries/popup';
import PosPaymentPopup from './popups/ns-pos-payment-popup';
import PosConfirmPopup from './popups/ns-pos-confirm-popup';
import nsPosQuantityPopupVue from './popups/ns-pos-quantity-popup.vue';
import { ProductQuantityPromise } from "./queues/products/product-quantity";
import nsPosDiscountPopupVue from './popups/ns-pos-discount-popup.vue';
import nsPosOrderTypePopupVue from './popups/ns-pos-order-type-popup.vue';
import { nsSnackBar } from '../../../bootstrap';
import nsPosCustomerPopupVue from './popups/ns-pos-customer-popup.vue';
import { ProductsQueue } from "./queues/order/products-queue";
import { CustomerQueue } from "./queues/order/customer-queue";
import { PaymentQueue } from "./queues/order/payment-queue";

export default {
    name: 'ns-pos-cart',
    data: () => {
        return {
            popup : null,
            products: [],
            types: [],
            order: {},
        }
    },
    computed: {
        selectedType() {
            return this.order.type ? this.order.type.label : 'N/A';
        },
        customerName() {
            return this.order.customer ? this.order.customer.name : 'N/A';
        }
    },
    mounted() {
        POS.types.subscribe( types => this.types = types );
        POS.order.subscribe( order => {
            this.order   =   order;
        });
        POS.products.subscribe( products => {
            this.products = products;
            this.$forceUpdate();
        });
    },
    methods: {
        openDiscountPopup( reference, type ) {
            Popup.show( nsPosDiscountPopupVue, { 
                reference,
                type,
                onSubmit( response ) {
                    if ( type === 'product' ) {
                        POS.updateProduct( reference, response );
                    } else if ( type === 'cart' ) {
                        POS.updateCart( reference, response );
                    }
                }
            }, {
                popupClass: 'bg-white h:2/3 shadow-lg xl:w-1/4 lg:w-2/5 md:w-2/3 w-full'
            })
        },

        selectCustomer() {
            Popup.show( nsPosCustomerPopupVue );
        },

        toggleMode( product ) {
            if ( product.mode === 'normal' ) {
                Popup.show( PosConfirmPopup, {
                    title: 'Enable WholeSale Price',
                    message: 'Would you like to switch to wholesale price ?',
                    onAction( action ) {
                        if ( action ) {
                            POS.updateProduct( product, { mode: 'wholesale' });
                        }
                    }
                });
            } else {
                Popup.show( PosConfirmPopup, {
                    title: 'Enable Normal Price',
                    message: 'Would you like to switch to normal price ?',
                    onAction( action ) {
                        if ( action ) {
                            POS.updateProduct( product, { mode: 'normal' });
                        }
                    }
                });
            }
        },
        remove( product ) {
            Popup.show( PosConfirmPopup, {
                title: 'Confirm Your Action',
                message: 'Would you like to delete this product ?',
                onAction( action ) {
                    if ( action ) {
                        POS.removeProduct( product );
                    }
                }
            });
        },

        /**
         * This will use the previously used 
         * popup to run the promise.
         */
        changeQuantity( product ) {
            const quantityPromise   =   new ProductQuantityPromise( product );
            quantityPromise.run({ unit_id : product.unit_id }).then( result => {
                POS.updateProduct( product, result );
            })
        },

        async payOrder() {
            const queues    =   [
                ProductsQueue,
                CustomerQueue,
                PaymentQueue
            ];

            for( let index in queues ) {
                try {
                    const promise   =   new queues[ index ]( this.order );
                    const response  =   await promise.run();
                } catch( exception ) {
                    /**
                     * in case there is something broken
                     * on the promise, we just stop the queue.
                     */
                    return false;    
                }
            }
        },
        openOrderType() {
            Popup.show( nsPosOrderTypePopupVue );
        }
    }
}
</script>