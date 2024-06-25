<template>
    <div class="-mx-4 flex flex-wrap">
        <div class="flex-auto">
            <div class="w-full mb-2 flex-wrap flex">
                <div class="w-full mb-2 px-4">
                    <h3 class="font-semibold text-secondary pb-2 border-b border-info-primary">{{ __( 'Payment Summary' ) }}</h3>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="elevation-surface border p-2 flex justify-between items-start">
                        <div>
                            <h4 class="text-semibold text-primary">{{ __( 'Sub Total' ) }}</h4>
                        </div>
                        <div class="font-semibold text-secondary">{{ nsCurrency( order.subtotal ) }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start text-primary elevation-surface error border">
                        <div>
                            <h4 class="text-semibold">
                                <span class="">{{ __( 'Discount' ) }}</span>
                                <span class=" ml-1" v-if="order.discount_type === 'percentage'">({{ order.discount_percentage }}%)</span>
                                <span class=" ml-1" v-if="order.discount_type === 'flat'">(Flat)</span>
                            </h4>
                        </div>
                        <div class="font-semibold text-primary">{{ nsCurrency( order.discount ) }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start elevation-surface border">
                        <div>
                            <span class="text-semibold text-primary">{{ __( 'Shipping' ) }}</span>
                        </div>
                        <div class="font-semibold text-secondary">{{ nsCurrency( order.shipping ) }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start text-primary elevation-surface error border">
                        <div>
                            <span class="text-semibold">
                                {{ __( 'Coupons' ) }}
                            </span>
                        </div>
                        <div class="font-semibold text-primary">{{ nsCurrency( order.total_coupons ) }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start border ns-notice success">
                        <div>
                            <span class="text-semibold">{{ __( 'Total' ) }}</span>
                        </div>
                        <div class="font-semibold text-primary">{{ nsCurrency( order.total ) }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start text-primary elevation-surface info border">
                        <div>
                            <span class="text-semibold">{{ __( 'Taxes' ) }}</span>
                        </div>
                        <div class="font-semibold">{{ nsCurrency( order.tax_value ) }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start text-primary elevation-surface error border">
                        <div>
                            <span class="text-semibold">{{ __( 'Change' ) }}</span>
                        </div>
                        <div class="font-semibold">{{ nsCurrency( order.change ) }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start elevation-surface border">
                        <div>
                            <span class="text-semibold">{{ __( 'Paid' ) }}</span>
                        </div>
                        <div class="font-semibold">{{ nsCurrency( order.tendered ) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 w-full md:w-1/2 lg:w-2/4 mb-2">
            <div class="mb-2">
                <h3 class="font-semibold text-secondary pb-2 border-b border-info-primary">{{ __( 'Order Status' ) }}</h3>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start elevation-surface border">
                <div>
                    <h4 class="text-semibold text-primary">
                        <span>{{ __( 'Customer' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-secondary" v-if="order">
                    <a class="border-b border-dashed border-info-primary" :href="systemUrls.customer_edit_url.replace( '#customer', order.customer.id )" target="_blank" rel="noopener noreferrer">{{ order.customer.first_name }} {{ order.customer.last_name }}</a>
                </div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start elevation-surface border">
                <div>
                    <h4 class="text-semibold text-primary">
                        <span>{{ __( 'Type' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-secondary">{{ labels.getTypeLabel( order.type ) }}</div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start elevation-surface border">
                <div>
                    <h4 class="text-semibold text-primary">
                        <span>{{ __( 'Delivery Status' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-secondary mt-2 md:mt-0 w-full md:w-auto">
                    <div class="w-full text-center">
                        <span @click="showDeliverySelect = true" v-if="! showDeliverySelect" class="font-semibold text-secondary border-b border-info-primary cursor-pointer border-dashed">{{ labels.getDeliveryStatus( order.delivery_status ) }}</span>
                    </div>
                    <div v-if="showDeliverySelect" class="flex-auto flex">
                        <div class="ns-select flex items-center justify-center">
                            <select ref="process_status" class="flex-auto border-info-primary rounded-lg" v-model="order.delivery_status">
                                <option
                                    v-for="( option, index ) of deliveryStatuses"
                                    :key="index"
                                    :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="pl-2 flex">
                            <ns-close-button @click="showDeliverySelect = false"></ns-close-button>
                            <button @click="submitDeliveryStatus( order )" class="bg-success-primary text-white rounded-full px-2 py-1">{{ __( 'Save' ) }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-2 p-2 flex flex-col md:flex-row justify-between items-center elevation-surface border">
                <div>
                    <h4 class="text-semibold text-primary">
                        <span>{{ __( 'Processing Status' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-secondary mt-2 md:mt-0 w-full md:w-auto">
                    <div class="w-full text-center">
                        <span @click="showProcessingSelect = true" v-if="! showProcessingSelect" class="border-b border-info-primary cursor-pointer border-dashed">{{ labels.getProcessingStatus( order.process_status ) }}</span>
                    </div>
                    <div class="flex-auto flex" v-if="showProcessingSelect">
                        <div class="ns-select flex items-center justify-center">
                            <select ref="process_status" class="flex-auto border-info-primary rounded-lg" v-model="order.process_status">
                                <option
                                    v-for="( option, index ) of processingStatuses"
                                    :key="index"
                                    :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="pl-2 flex">
                            <ns-close-button @click="showProcessingSelect = false"></ns-close-button>
                            <button @click="submitProcessingChange( order )" class="bg-success-primary text-white rounded-full px-2 py-1">{{ __( 'Save' ) }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start elevation-surface border">
                <div>
                    <h4 class="text-semibold text-primary">
                        <span>{{ __( 'Payment Status' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-secondary">{{ labels.getPaymentStatus( order.payment_status ) }}</div>
            </div>
        </div>

        <div class="px-4 w-full md:w-1/2 lg:w-2/4 mb-2">
            <div class="mb-2">
                <h3 class="font-semibold text-secondary pb-2 border-b border-info-primary">{{ __( 'Products' ) }}</h3>
            </div>
            <div :key="product.id" v-for="product of order.products" class="p-2 flex justify-between items-start elevation-surface border mb-6">
                <div>
                    <h4 class="text-semibold text-primary">{{ product.name }} (x{{ product.quantity }})</h4>
                    <p class="text-secondary text-sm">{{ product.unit.name || 'N/A' }}</p>
                </div>
                <div class="font-semibold text-secondary">{{ nsCurrency( product.total_price ) }}</div>
            </div>

            <div class="mb-2">
                <h3 class="font-semibold text-secondary pb-2 border-b border-info-primary flex justify-between">
                    <span>{{ __( 'Refunded Products' ) }}</span>
                    <a href="javascript:void(0)" @click="openRefunds()" class="border-b border-info-primary border-dashed">{{ __( 'All Refunds' ) }}</a>
                </h3>
            </div>
            <div :key="index" v-for="(product, index) of order.refunded_products" class="p-2 flex justify-between items-start elevation-surface border  mb-6">
                <div>
                    <h4 class="text-semibold text-primary">{{ product.order_product.name }} (x{{ product.quantity }})</h4>
                    <p class="text-secondary text-sm">{{ product.unit.name || 'N/A' }} | <span class="rounded-full px-2" :class="product.condition === 'damaged' ? 'bg-error-tertiary text-white' : 'bg-info-tertiary text-white'">{{ product.condition }}</span></p>
                </div>
                <div class="font-semibold text-secondary">{{ nsCurrency( product.total_price ) }}</div>
            </div>
        </div>
    </div>
</template>
<script>
import Labels from "~/libraries/labels";
import { __ } from '~/libraries/lang';
import { Popup } from '~/libraries/popup';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import nsOrdersRefundPopupVue from '~/popups/ns-orders-refund-popup.vue';
import { nsCurrency } from '~/filters/currency';

export default {
    props: [ 'order' ],
    data() {
        return {
            processingStatuses,
            deliveryStatuses,
            labels: new Labels,
            showProcessingSelect: false,
            showDeliverySelect: false,
            systemUrls,
        }
    },
    mounted() {
        // ...
    },
    methods: {
        __,
        nsCurrency,
        submitProcessingChange() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Would you proceed ?' ),
                message: __( 'The processing status of the order will be changed. Please confirm your action.' ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( `/api/orders/${this.order.id}/processing`, {
                            process_status: this.order.process_status
                        }).subscribe({
                            next: result => {
                                this.showProcessingSelect   =   false;
                                nsSnackBar.success( result.message ).subscribe();
                            },
                            error: ( error ) => {
                                nsSnackBar.error( error.message || __( 'Unexpected error occurred.' ) ).subscribe();
                            }
                        })
                    }
                }
            })
        },

        openRefunds() {
            try {
                const result    =   new Promise( ( resolve, reject ) => {
                    const order     =   this.order;
                    Popup.show( nsOrdersRefundPopupVue, { order, resolve, reject })
                });
            } catch( exception ) {
                // ...
            }
        },

        submitDeliveryStatus() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Would you proceed ?' ),
                message: __( 'The delivery status of the order will be changed. Please confirm your action.' ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( `/api/orders/${this.order.id}/delivery`, {
                            delivery_status: this.order.delivery_status
                        }).subscribe({
                            next: result => {
                                this.showDeliverySelect     =   false;
                                nsSnackBar.success( result.message ).subscribe();
                            },
                            error: ( error ) => {
                                nsSnackBar.error( error.message || __( 'Unexpected error occurred.' ) ).subscribe();
                            }
                        })
                    }
                }
            })
        }
    }
}
</script>
