<template>
    <div id="pos-cart" class="flex-auto flex flex-col">
        <div id="tools" class="flex pl-2 ns-tab" v-if="visibleSection === 'cart'">
            <div @click="switchTo( 'cart' )" class="flex cursor-pointer rounded-tl-lg rounded-tr-lg px-3 py-2 font-semibold active tab">
                <span>{{ __( 'Cart' ) }}</span>
                <span v-if="order" class="flex items-center justify-center text-sm rounded-full h-6 w-6 bg-green-500 text-white ml-1">{{ order.products.length }}</span>
            </div>
            <div @click="switchTo( 'grid' )" class="cursor-pointer rounded-tl-lg rounded-tr-lg px-3 py-2 border-t border-r border-l inactive tab">
                {{ __( 'Products' ) }}
            </div>
        </div>
        <div class="rounded shadow ns-tab-item flex-auto flex overflow-hidden">
            <div class="cart-table flex flex-auto flex-col overflow-hidden">
                <div id="cart-toolbox" class="w-full p-2 border-b">
                    <div class="border rounded overflow-hidden">
                        <div class="flex flex-wrap">
                            <div class="ns-button">
                                <button @click="openNotePopup()" class="w-full h-10 px-3 outline-none">
                                    <i class="las la-comment"></i>
                                    <span class="ml-1 hidden md:inline-block">{{ __( 'Comments' ) }}</span>
                                </button>
                            </div>
                            <hr class="h-10" style="width: 1px">
                            <div class="ns-button">
                                <button @click="selectTaxGroup()" class="w-full h-10 px-3 outline-none flex items-center">
                                    <i class="las la-balance-scale-left"></i>
                                    <span class="ml-1 hidden md:inline-block">{{ __( 'Taxes' ) }}</span>
                                    <span v-if="order.taxes && order.taxes.length > 0" class="ml-1 rounded-full flex items-center justify-center h-6 w-6 bg-info-secondary text-white">{{ order.taxes.length }}</span>
                                </button>
                            </div>
                            <hr class="h-10" style="width: 1px">
                            <div class="ns-button">
                                <button @click="selectCoupon()" class="w-full h-10 px-3 outline-none flex items-center">
                                    <i class="las la-tags"></i>
                                    <span class="ml-1 hidden md:inline-block">{{ __( 'Coupons' ) }}</span>
                                    <span v-if="order.coupons && order.coupons.length > 0" class="ml-1 rounded-full flex items-center justify-center h-6 w-6 bg-info-secondary text-white">{{ order.coupons.length }}</span>
                                </button>
                            </div>
                            <hr class="h-10" style="width: 1px">
                            <div class="ns-button">
                                <button @click="defineOrderSettings()" class="w-full h-10 px-3 outline-none flex items-center">
                                    <i class="las la-tools"></i>
                                    <span class="ml-1 hidden md:inline-block">{{ __( 'Settings' ) }}</span>
                                </button>
                            </div>
                            <hr class="h-10" style="width: 1px">
                            <div class="ns-button" v-if="options.ns_pos_quick_product === 'yes'">
                                <button @click="openAddQuickProduct()" class="w-full h-10 px-3 outline-none flex items-center">
                                    <i class="las la-plus"></i>
                                    <span class="ml-1 hidden md:inline-block">{{ __( 'Product' ) }}</span>
                                </button>
                            </div>
                            <hr class="h-10" style="width: 1px">
                        </div>
                    </div>
                </div>
                <div id="cart-table-header" class="w-full text-primary font-semibold flex">
                    <div class="w-full lg:w-4/6 p-2 border border-l-0 border-t-0">{{ __( 'Product' ) }}</div>
                    <div class="hidden lg:flex lg:w-1/6 p-2 border-b border-t-0">{{ __( 'Quantity' ) }}</div>
                    <div class="hidden lg:flex lg:w-1/6 p-2 border border-r-0 border-t-0">{{ __( 'Total' ) }}</div>
                </div>
                <div id="cart-products-table" class="flex flex-auto flex-col overflow-auto">
                    
                    <!-- Loop Procuts On Cart -->

                    <div class="text-primary flex" v-if="products.length === 0">
                        <div class="w-full text-center py-4 border-b">
                            <h3>{{ __( 'No products added...' ) }}</h3>
                        </div>
                    </div>

                    <div :product-index="index" :key="product.barcode" class="product-item flex" v-for="(product, index) of products">
                        <div class="w-full lg:w-4/6 p-2 border border-l-0 border-t-0">
                            <div class="flex justify-between product-details mb-1">
                                <h3 class="font-semibold">
                                    {{ product.name }} &mdash; {{ product.unit_name }}
                                </h3>
                                <div class="-mx-1 flex product-options">
                                    <div class="px-1"> 
                                        <a @click="removeUsingIndex( index )" class="hover:text-error-secondary cursor-pointer outline-none border-dashed py-1 border-b border-error-secondary text-sm">
                                            <i class="las la-trash text-xl"></i>
                                        </a>
                                    </div>
                                    <div class="px-1" v-if="options.ns_pos_allow_wholesale_price && allowQuantityModification( product )"> 
                                        <a :class="product.mode === 'wholesale' ? 'text-success-secondary border-success-secondary' : 'border-info-primary'" @click="toggleMode( product, index )" class="cursor-pointer outline-none border-dashed py-1 border-b  text-sm">
                                            <i class="las la-award text-xl"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between product-controls">
                                <div class="-mx-1 flex flex-wrap">
                                    <div class="px-1 w-1/2 md:w-auto mb-1">
                                        <a
                                            @click="changeProductPrice( product )"
                                            :class="product.mode === 'wholesale' ? 'text-success-secondary hover:text-success-secondary border-success-secondary' : 'border-info-primary'"
                                            class="cursor-pointer outline-none border-dashed py-1 border-b  text-sm"
                                        >{{ __( 'Price' ) }} : {{ nsCurrency( product.unit_price ) }}</a>
                                    </div>
                                    <div class="px-1 w-1/2 md:w-auto mb-1"> 
                                        <a v-if="allowQuantityModification( product )" @click="openDiscountPopup( product, 'product', index )" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Discount' ) }} <span v-if="product.discount_type === 'percentage'">{{ product.discount_percentage }}%</span> : {{ nsCurrency( product.discount ) }}</a>
                                    </div>
                                    <div class="px-1 w-1/2 md:w-auto mb-1 lg:hidden"> 
                                        <a v-if="allowQuantityModification( product )" @click="changeQuantity( product, index )" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Quantity' ) }}: {{ product.quantity }}</a>
                                    </div>
                                    <div class="px-1 w-1/2 md:w-auto mb-1 lg:hidden"> 
                                        <span class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Total :' ) }} {{ nsCurrency( product.total_price ) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div @click="changeQuantity( product, index )" :class="allowQuantityModification( product ) ? 'cursor-pointer ns-numpad-key' : ''" class="hidden lg:flex w-1/6 p-2 border-b items-center justify-center">
                            <span v-if="allowQuantityModification( product )" class="border-b border-dashed border-info-primary p-2">{{ product.quantity }}</span>
                        </div>
                        <div class="hidden lg:flex w-1/6 p-2 border border-r-0 border-t-0 items-center justify-center">{{ nsCurrency( product.total_price ) }}</div>
                    </div>
                    
                    <!-- End Loop -->

                </div>
                <div id="cart-products-summary" class="flex">
                    <table class="table ns-table w-full text-sm " v-if="visibleSection === 'both'">
                        <tbody>
                            <tr>
                                <td width="200" class="border p-2">
                                    <a @click="selectCustomer()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Customer' ) }}: {{ customerName }}</a>
                                </td>
                                <td width="200" class="border p-2">{{ __( 'Sub Total' ) }}</td>
                                <td width="200" class="border p-2 text-right">{{ nsCurrency( order.subtotal ) }}</td>
                            </tr>
                            <tr v-if="order.coupons.length > 0">
                                <td width="200" class="border p-2"></td>
                                <td width="200" class="border p-2">
                                    <a @click="selectCoupon()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Coupons' ) }}</a>
                                </td>
                                <td width="200" class="border p-2 text-right">{{ nsCurrency( summarizeCoupons() ) }}</td>
                            </tr>
                            <tr>
                                <td width="200" class="border p-2">
                                    <a @click="openOrderType()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Type' ) }}: {{ selectedType }}</a>
                                </td>
                                <td width="200" class="border p-2">
                                    <span>{{ __( 'Discount' ) }}</span>
                                    <span v-if="order.discount_type === 'percentage'">({{ order.discount_percentage }}%)</span>
                                    <span v-if="order.discount_type === 'flat'">({{ __( 'Flat' ) }})</span>
                                </td>
                                <td width="200" class="border p-2 text-right">
                                    <a @click="openDiscountPopup( order, 'cart' )" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ nsCurrency( order.discount ) }}</a>
                                </td>
                            </tr>
                            <tr v-if="order.type && order.type.identifier === 'delivery'">
                                <td width="200" class="border p-2"></td>
                                <td width="200" class="border p-2">
                                    <a @click="openShippingPopup()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Shipping' ) }}</a>
                                </td>
                                <td width="200" class="border p-2 text-right">{{ nsCurrency( order.shipping ) }}</td>
                            </tr>
                            <tr class="success">
                                <td width="200" class="border p-2">
                                    <template v-if="options.ns_pos_vat !== 'disabled'">
                                        <template v-if="order && options.ns_pos_tax_type === 'exclusive'">
                                            <a v-if="options.ns_pos_price_with_tax === 'yes'" @click="openTaxSummary()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Tax Included' ) }}: {{ nsCurrency( order.total_tax_value + order.products_tax_value ) }}</a>
                                            <a v-else-if="options.ns_pos_price_with_tax === 'no'" @click="openTaxSummary()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Tax' ) }}: {{ nsCurrency( order.total_tax_value ) }}</a>
                                        </template>
                                        <template v-else-if="order && options.ns_pos_tax_type === 'inclusive'">
                                            <a v-if="options.ns_pos_price_with_tax === 'yes'" @click="openTaxSummary()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Tax Included' ) }}: {{ nsCurrency( order.total_tax_value + ( order.products_tax_value ) ) }}</a>
                                            <a v-else-if="options.ns_pos_price_with_tax === 'no'" @click="openTaxSummary()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Tax' ) }}: {{ nsCurrency( order.total_tax_value ) }}</a>
                                        </template>
                                    </template>
                                </td>
                                <td width="200" class="border p-2">{{ __( 'Total' ) }}</td>
                                <td width="200" class="border p-2 text-right">{{ nsCurrency( order.total ) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table ns-table w-full text-sm" v-if="visibleSection === 'cart'">
                        <tbody>
                            <tr>
                                <td width="200" class="border p-2">
                                    <a @click="selectCustomer()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Customer' ) }}: {{ customerName }}</a>
                                </td>
                                <td width="200" class="border p-2">
                                    <div class="flex justify-between">
                                        <span>{{ __( 'Sub Total' ) }}</span>
                                        <span>{{ nsCurrency( order.subtotal ) }}</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="order.coupons.length > 0">
                                <td width="200" class="border p-2"></td>
                                <td width="200" class="border p-2">
                                    <a @click="selectCoupon()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Coupons' ) }}</a>
                                </td>
                                <td width="200" class="border p-2 text-right">{{ nsCurrency( summarizeCoupons() ) }}</td>
                            </tr>
                            <tr>
                                <td width="200" class="border p-2">
                                    <a @click="openOrderType()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Type' ) }}: {{ selectedType }}</a>
                                </td>
                                <td width="200" class="border p-2">
                                    <div class="flex justify-between items-center">
                                        <p>
                                            <span>{{ __( 'Discount' ) }}</span>
                                            <span v-if="order.discount_type === 'percentage'">({{ order.discount_percentage }}%)</span>
                                            <span v-if="order.discount_type === 'flat'">({{ __( 'Flat' ) }})</span>
                                        </p>
                                        <a @click="openDiscountPopup( order, 'cart' )" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ nsCurrency( order.discount ) }}</a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="order.type && order.type.identifier === 'delivery'">
                                <td width="200" class="border p-2"></td>
                                <td width="200" class="border p-2">
                                    <a @click="openShippingPopup()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Shipping' ) }}</a>
                                    <span></span>                          
                                </td>
                            </tr>
                            <tr class="success">
                                <td width="200" class="border p-2">
                                    <template v-if="options.ns_pos_vat !== 'disabled'">
                                        <template v-if="order && options.ns_pos_tax_type === 'exclusive'">
                                            <a v-if="options.ns_pos_price_with_tax === 'yes'" @click="openTaxSummary()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Tax' ) }}: {{ nsCurrency( order.total_tax_value ) }}</a>
                                            <a v-else-if="options.ns_pos_price_with_tax === 'no'" @click="openTaxSummary()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Tax Inclusive' ) }}: {{ nsCurrency( order.total_tax_value + order.products_tax_value ) }}</a>
                                        </template>
                                        <template v-else-if="order && options.ns_pos_tax_type === 'inclusive'">
                                            <a v-if="options.ns_pos_price_with_tax === 'yes'" @click="openTaxSummary()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Tax Included' ) }}: {{ nsCurrency( order.total_tax_value ) }}</a>
                                            <a v-else-if="options.ns_pos_price_with_tax === 'no'" @click="openTaxSummary()" class="cursor-pointer outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ __( 'Tax Included' ) }}: {{ nsCurrency( order.total_tax_value + order.products_tax_value ) }}</a>
                                        </template>
                                    </template>
                                </td>
                                <td width="200" class="border p-2">
                                    <div class="flex justify-between w-full">
                                        <span>{{ __( 'Total' ) }}</span>
                                        <span>{{ nsCurrency( order.total ) }}</span>    
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="h-16 flex flex-shrink-0 border-t border-box-edge" id="cart-bottom-buttons">
                    <template v-for="button of (new Array(4)).fill()" v-if="Object.keys( cartButtons ).length === 0"> 
                        <div :class="takeRandomClass()" class="animate-pulse flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center  border-r  flex-auto">
                            <i class="mx-4 rounded-full bg-slate-300 h-5 w-5"></i>
                            <div class="text-lg mr-4 hidden md:flex md:flex-auto lg:text-2xl">
                                <div class="h-2 flex-auto bg-slate-200 rounded"></div>
                            </div>
                        </div>
                    </template>
                    <template v-for="component of cartButtons">
                        <component :is="component" :order="order" :settings="settings"></component>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { nsHooks, nsSnackBar } from '~/bootstrap';
import { Popup } from '~/libraries/popup';
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';
import switchTo from "~/libraries/pos-section-switch";

import { ProductQuantityPromise } from "./queues/products/product-quantity";

import nsPosPayButton from '~/pages/dashboard/pos/cart-buttons/ns-pos-pay-button.vue';
import nsPosHoldButton from '~/pages/dashboard/pos/cart-buttons/ns-pos-hold-button.vue';
import nsPosDiscountButton from '~/pages/dashboard/pos/cart-buttons/ns-pos-discount-button.vue';
import nsPosVoidButton from '~/pages/dashboard/pos/cart-buttons/ns-pos-void-button.vue';

import nsPosDiscountPopupVue from '~/popups/ns-pos-discount-popup.vue';
import PosConfirmPopup from '~/popups/ns-pos-confirm-popup.vue';
import nsPosOrderTypePopupVue from '~/popups/ns-pos-order-type-popup.vue';
import nsPosCustomerPopupVue from '~/popups/ns-pos-customer-select-popup.vue';
import nsPosShippingPopupVue from '~/popups/ns-pos-shipping-popup.vue';
import nsPosNotePopupVue from '~/popups/ns-pos-note-popup.vue';
import nsPosTaxPopupVue from '~/popups/ns-pos-tax-popup.vue';
import nsPosCouponsLoadPopupVue from '~/popups/ns-pos-coupons-load-popup.vue';
import nsPosOrderSettingsVue from '~/popups/ns-pos-order-settings.vue';
import nsPosProductPricePopupVue from '~/popups/ns-pos-product-price-popup.vue';
import nsPosQuickProductPopupVue from '~/popups/ns-pos-quick-product-popup.vue';

declare const POS, nsShortcuts, nsHotPress;

import { ref, markRaw } from '@vue/reactivity';
import { Order } from '~/interfaces/order';
import { Ref } from 'vue';

export default {
    name: 'ns-pos-cart',
    data: () => {
        return {
            popup : null,
            cartButtons: {},
            products: [],
            defaultCartButtons: {
                nsPosPayButton: markRaw( nsPosPayButton ),
                nsPosHoldButton: markRaw( nsPosHoldButton ),
                nsPosDiscountButton: markRaw( nsPosDiscountButton ),
                nsPosVoidButton: markRaw( nsPosVoidButton ),
            },
            visibleSection: null,
            visibleSectionSubscriber: null,
            cartButtonsSubscriber: null,
            optionsSubscriber: null,
            options: {},
            typeSubscribe: null,
            orderSubscribe: null,
            productSubscribe: null,
            settingsSubscribe: null,
            settings: {},
            types: [],
            order: ref({}) as Ref<Order>,
        }
    },
    computed: {
        selectedType() {
            return this.order.type ? this.order.type.label : 'N/A';
        },
        isVisible() {
            return this.visibleSection === 'cart';
        },
        customerName() {
            return this.order.customer ? `${this.order.customer.first_name || this.order.customer.last_name ? this.getFirstName() : this.getUserName() }` : 'N/A';
        },
        couponName() {
            return __( 'Apply Coupon' );
        }
    },
    mounted() {
        this.cartButtonsSubscriber  =   POS.cartButtons.subscribe( cartButtons => {
            this.cartButtons    =   cartButtons;
        });

        this.optionsSubscriber  =   POS.options.subscribe( options => {
            this.options    =   options;
        });

        this.typeSubscribe  =   POS.types.subscribe( types => this.types = types );

        this.orderSubscribe  =   POS.order.subscribe( order => {
            this.order   =   ref(order);
        });

        this.productSubscribe  =   POS.products.subscribe( products => {
            this.products = ref(products);
        });

        this.settingsSubscribe  =   POS.settings.subscribe( settings => {
            this.settings   =   ref(settings);
        });

        this.visibleSectionSubscriber   =   POS.visibleSection.subscribe( section => {
            this.visibleSection     =   ref(section);
        });

        /**
         * everytime the cart reset
         * we restore original buttons.
         */
        nsHooks.addAction( 'ns-before-cart-reset', 'ns-pos-cart-buttons', () => {
            POS.cartButtons.next( this.defaultCartButtons );
        });

        /**
         * let's register hotkeys
         */
        for( let shortcut in nsShortcuts ) {
            if ([ 
                    'ns_pos_keyboard_shipping', 
                ].includes( shortcut ) ) {
                nsHotPress
                    .create( 'ns_pos_keyboard_shipping' )
                    .whenNotVisible([ '.is-popup' ])
                    .whenPressed( nsShortcuts[ shortcut ] !== null ? nsShortcuts[ shortcut ].join( '+' ) : null, ( event ) => {
                        event.preventDefault();
                        this.openShippingPopup();
                });
            }

            if ([ 
                    'ns_pos_keyboard_note', 
                ].includes( shortcut ) ) {
                nsHotPress
                    .create( 'ns_pos_keyboard_note' )
                    .whenNotVisible([ '.is-popup' ])
                    .whenPressed( nsShortcuts[ shortcut ] !== null ? nsShortcuts[ shortcut ].join( '+' ) : null, ( event ) => {
                        event.preventDefault();
                        this.openNotePopup();
                });
            }
        }
    },
    unmounted() {
        this.visibleSectionSubscriber.unsubscribe();
        this.typeSubscribe.unsubscribe();
        this.orderSubscribe.unsubscribe();
        this.productSubscribe.unsubscribe();
        this.settingsSubscribe.unsubscribe();
        this.optionsSubscriber.unsubscribe();
        this.cartButtonsSubscriber.unsubscribe();
        
        nsHotPress.destroy( 'ns_pos_keyboard_shipping' );
        nsHotPress.destroy( 'ns_pos_keyboard_note' );
    },
    methods: {
        __,
        nsCurrency,

        switchTo,

        getFirstName() {
            return `${this.order.customer.first_name || ''} ${this.order.customer.last_name || '' }`;
        },

        getUserName() {
            return this.order.customer.username;
        },

        takeRandomClass() {
            return 'border-gray-500 bg-gray-400 text-white hover:bg-gray-500';
        },

        openAddQuickProduct() {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsPosQuickProductPopupVue, { resolve, reject })
            });

            promise.then( _ => {
                // ...
            }).catch( _ => {
                // ...
            })
        },

        summarizeCoupons() {
            const coupons   =   this.order.coupons.map( coupon => coupon.value );

            if ( coupons.length > 0 ) {
                return coupons.reduce( ( before, after ) => before + after );
            }

            return 0;
        },

        async changeProductPrice( product ) {
            if ( ! this.settings.edit_purchase_price ) {
                return nsSnackBar.error( __( `You don't have the right to edit the purchase price.` ) ).subscribe();
            }

            if ( product.product_type === 'dynamic' ) {
                return nsSnackBar.error( __( 'Dynamic product can\'t have their price updated.' ) ).subscribe();
            }

            if ( this.settings.unit_price_editable ) {
                try {
                    const newPrice  =   await new Promise( ( resolve, reject ) => {
                        return Popup.show( nsPosProductPricePopupVue, { product: Object.assign({}, product ), resolve, reject })
                    });

                    const quantities  =   {
                        ...product.$quantities(), 
                        ...{
                            custom_price_edit : newPrice,
                            custom_price_with_tax: newPrice,
                            custom_price_without_tax: newPrice
                        }
                    }

                    product.$quantities     =   () => quantities;

                    /**
                     * We need to change the price mode
                     * to avoid restoring the original prices.
                     */
                    product.mode    =   'custom';
                                        
                    POS.recomputeProducts( POS.products.getValue() );
                    POS.refreshCart();

                    return nsSnackBar.success( __( 'The product price has been updated.' ) ).subscribe();
                } catch( exception ) {
                    if ( exception !== false ) {
                        nsSnackBar.error( exception ).subscribe();
                        throw exception;
                    }
                }
            } else {
                return nsSnackBar.error( __( 'The editable price feature is disabled.' ) ).subscribe();
            }
        },

        async selectCoupon() {
            try {
                const response  =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosCouponsLoadPopupVue, { resolve, reject })
                })
            } catch( exception ) {
                
            }
        },

        async defineOrderSettings() {
            if ( ! this.settings.edit_settings ) {
                return nsSnackBar.error( __( 'You\'re not allowed to edit the order settings.' ) ).subscribe();
            }

            try {
                const response  =   await new Promise<{}>( ( resolve, reject) => {
                    Popup.show( nsPosOrderSettingsVue, { resolve, reject, order : this.order });
                });

                /**
                 * We'll update the order
                 */
                POS.order.next({ ...this.order, ...response });

            } catch( exception ) {
                // we shouldn't catch any exception here.
            }
        },

        async openNotePopup() {
            try {
                const response  =   await new Promise<{}>( ( resolve, reject ) => {
                    const note              =   this.order.note;
                    const note_visibility   =   this.order.note_visibility;
                    Popup.show( nsPosNotePopupVue, { resolve, reject, note, note_visibility });
                });

                const order     =   { ...this.order, ...response };
                POS.order.next( order );
            } catch( exception ) {
                if ( exception !== false ) {
                    nsSnackBar.error( exception.message ).subscribe();
                }
            }
        },

        async selectTaxGroup( activeTab = 'settings' ) {
            try {
                const response              =   await new Promise<{}>( ( resolve, reject ) => {
                    const taxes             =   this.order.taxes;
                    const tax_group_id      =   this.order.tax_group_id;
                    const tax_type          =   this.order.tax_type;
                    Popup.show( nsPosTaxPopupVue, { resolve, reject, taxes, tax_group_id, tax_type, activeTab })
                });

                const order             =   { ...this.order, ...response };
                
                POS.order.next( order );
                POS.refreshCart();

            } catch( exception ) {
                // popup is closed... not needed to log or do anything else
            }
        },

        openTaxSummary() {
            this.selectTaxGroup( 'summary' );
        },

        selectCustomer() {
            Popup.show( nsPosCustomerPopupVue );
        },

        async openDiscountPopup( reference, type, index = null ) {
            if ( ! this.settings.products_discount && type === 'product' ) {
                return nsSnackBar.error( __( `You're not allowed to add a discount on the product.` ) ).subscribe();
            }

            if ( ! this.settings.cart_discount && type === 'cart' ) {
                return nsSnackBar.error( __( `You're not allowed to add a discount on the cart.` ) ).subscribe();
            }

            if ( type === 'product' ) {
                reference.disable_flat = true;
            }

            try {
                const promise   =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosDiscountPopupVue, { 
                        reference,
                        resolve,
                        reject,
                        type,
                        onSubmit( response ) {
                            /**
                             * we should check here, if the discount is flat, we'll make sure
                             * the amount doesn't exceed the total_price of the product.
                             */
                            if ( response.discount_type === 'flat' && response.discount > reference.total_price ) {
                                return nsSnackBar.error( __( 'The discount amount can\'t exceed the total price of the product.' ) ).subscribe();
                            }
                            
                            
                            if ( type === 'product' ) {
                                POS.updateProduct( reference, response, index );
                            } else if ( type === 'cart' ) {
                                POS.updateCart( reference, response );
                            }
                        }
                    }, {
                        popupClass: 'bg-white h:2/3 shadow-lg xl:w-1/4 lg:w-2/5 md:w-2/3 w-full'
                    })
                })
            } catch ( exception ) {
                // the popup might just be closed...
            }
        },

        toggleMode( product, index ) {
            if ( ! this.options.ns_pos_allow_wholesale_price ) {
                return nsSnackBar.error( __( 'Unable to change the price mode. This feature has been disabled.' ) ).subscribe();
            }

            if ( product.mode === 'normal' ) {
                Popup.show( PosConfirmPopup, {
                    title: __( 'Enable WholeSale Price' ),
                    message: __( 'Would you like to switch to wholesale price ?' ),
                    onAction( action ) {
                        if ( action ) {
                            POS.updateProduct( product, { mode: 'wholesale' }, index );
                        }
                    }
                });
            } else {
                Popup.show( PosConfirmPopup, {
                    title: __( 'Enable Normal Price' ),
                    message: __( 'Would you like to switch to normal price ?' ),
                    onAction( action ) {
                        if ( action ) {
                            POS.updateProduct( product, { mode: 'normal' }, index );
                        }
                    }
                });
            }
        },
        removeUsingIndex( index ) {
            Popup.show( PosConfirmPopup, {
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to delete this product ?' ),
                onAction( action ) {
                    if ( action ) {
                        POS.removeProductUsingIndex( index );
                    }
                }
            });
        },

        allowQuantityModification( product ) {
            return product.product_type === 'product';
        },

        /**
         * This will use the previously used 
         * popup to run the promise.
         */
        changeQuantity( product, index ) {
            if ( this.allowQuantityModification( product ) ) {
                const quantityPromise   =   new ProductQuantityPromise( product );
                quantityPromise.run({ 
                    unit_quantity_id    : product.unit_quantity_id, 
                    unit_name           : product.unit_name, 
                    $quantities         : product.$quantities 
                }).then( result => {
                    POS.updateProduct( product, result, index );
                });
            }
        },

        openOrderType() {
            Popup.show( nsPosOrderTypePopupVue );
        },

        openShippingPopup() {
            Popup.show( nsPosShippingPopupVue );
        }
    }
}
</script>