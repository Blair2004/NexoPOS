<template>
    <div class="-mx-4 flex flex-wrap">
        <div class="flex-auto">
            <div class="w-full mb-2 flex-wrap flex">
                <div class="w-full mb-2 px-4">
                    <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">{{ __( 'Payment Summary' ) }}</h3>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="bg-gray-200 p-2 flex justify-between items-start">
                        <div>
                            <h4 class="text-semibold text-gray-700">{{ __( 'Sub Total' ) }}</h4>
                        </div>
                        <div class="font-semibold text-gray-800">{{ order.subtotal | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-red-400 text-white">
                        <div>
                            <h4 class="text-semibold">
                                <span>{{ __( 'Discount' ) }}</span>
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
                            <h4 class="text-semibold text-gray-700">{{ __( 'Shipping' ) }}</h4>
                        </div>
                        <div class="font-semibold text-gray-800">{{ order.shipping | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-red-400 text-white">
                        <div>
                            <h4 class="text-semibold">
                                <span>{{ __( 'Coupons' ) }}</span>
                            </h4>
                        </div>
                        <div class="font-semibold">{{ order.total_coupons | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-blue-400 text-white">
                        <div>
                            <h4 class="text-semibold">{{ __( 'Total' ) }}</h4>
                        </div>
                        <div class="font-semibold">{{ order.total | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-yellow-400 text-gray-700">
                        <div>
                            <h4 class="text-semibold ">{{ __( 'Taxes' ) }}</h4>
                        </div>
                        <div class="font-semibold">{{ order.tax_value | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start text-gray-700 bg-gray-100">
                        <div>
                            <h4 class="text-semibold">{{ __( 'Change' ) }}</h4>
                        </div>
                        <div class="font-semibold">{{ order.change | currency }}</div>
                    </div>
                </div>
                <div class="mb-2 w-full md:w-1/2 px-4">
                    <div class="p-2 flex justify-between items-start bg-teal-500 text-white">
                        <div>
                            <h4 class="text-semibold">{{ __( 'Paid' ) }}</h4>
                        </div>
                        <div class="font-semibold">{{ order.tendered | currency }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="px-4 w-full md:w-1/2 lg:w-2/4 mb-2">
            <div class="mb-2">
                <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">{{ __( 'Order Status' ) }}</h3>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>{{ __( 'Customer' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ order.nexopos_customers_name }}</div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>{{ __( 'Type' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getTypeLabel( order.type ) }}</div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>{{ __( 'Delivery Status' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800 mt-2 md:mt-0 w-full md:w-auto">
                    <div class="w-full text-center">
                        <span @click="showDeliverySelect = true" v-if="! showDeliverySelect" class="font-semibold text-gray-800 border-b border-blue-400 cursor-pointer border-dashed">{{ labels.getDeliveryStatus( order.delivery_status ) }}</span>
                    </div>
                    <div v-if="showDeliverySelect" class="flex-auto flex">
                        <select ref="process_status" class="flex-auto border-blue-400 rounded-lg" v-model="order.delivery_status">
                            <option 
                                v-for="( option, index ) of deliveryStatuses" 
                                :key="index" 
                                :value="option.value">{{ option.label }}</option>
                        </select>
                        <div class="pl-2 flex">
                            <ns-close-button @click="showDeliverySelect = false"></ns-close-button>
                            <button @click="submitDeliveryStatus( order )" class="bg-green-400 text-white rounded-full px-2 py-1">{{ __( 'Save' ) }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-2 p-2 flex flex-col md:flex-row justify-between items-center bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>{{ __( 'Processing Status' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800 mt-2 md:mt-0 w-full md:w-auto">
                    <div class="w-full text-center">
                        <span @click="showProcessingSelect = true" v-if="! showProcessingSelect" class="border-b border-blue-400 cursor-pointer border-dashed">{{ labels.getProcessingStatus( order.process_status ) }}</span>
                    </div>
                    <div class="flex-auto flex" v-if="showProcessingSelect">
                        <select ref="process_status" class="flex-auto border-blue-400 rounded-lg" v-model="order.process_status">
                            <option 
                                v-for="( option, index ) of processingStatuses" 
                                :key="index" 
                                :value="option.value">{{ option.label }}</option>
                        </select>
                        <div class="pl-2 flex">
                            <ns-close-button @click="showProcessingSelect = false"></ns-close-button>
                            <button @click="submitProcessingChange( order )" class="bg-green-400 text-white rounded-full px-2 py-1">{{ __( 'Save' ) }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-2 p-2 flex justify-between items-start bg-gray-200">
                <div>
                    <h4 class="text-semibold text-gray-700">
                        <span>{{ __( 'Payment Status' ) }}</span>
                    </h4>
                </div>
                <div class="font-semibold text-gray-800">{{ labels.getPaymentStatus( order.payment_status ) }}</div>
            </div>
        </div>

        <div class="px-4 w-full md:w-1/2 lg:w-2/4 mb-2">
            <div class="mb-2">
                <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">{{ __( 'Products' ) }}</h3>
            </div>
            <div :key="product.id" v-for="product of order.products" class="p-2 flex justify-between items-start bg-gray-200 mb-2">
                <div>
                    <h4 class="text-semibold text-gray-700">{{ product.name }} (x{{ product.quantity }})</h4>
                    <p class="text-gray-600 text-sm">{{ product.unit.name || 'N/A' }}</p>
                </div>
                <div class="font-semibold text-gray-800">{{ product.total_price | currency }}</div>
            </div>

            <div class="mb-2">
                <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">{{ __( 'Refunded Products' ) }}</h3>
            </div>
            <div :key="product.id" v-for="product of order.refunded_products" class="p-2 flex justify-between items-start bg-gray-200  mb-2">
                <div>
                    <h4 class="text-semibold text-gray-700">{{ product.product.name }} (x{{ product.quantity }})</h4>
                    <p class="text-gray-600 text-sm">{{ product.unit.name || 'N/A' }} | <span class="rounded-full px-2" :class="product.condition === 'damaged' ? 'bg-red-400 text-white' : 'bg-blue-400 text-white'">{{ product.condition }}</span></p>
                </div>
                <div class="font-semibold text-gray-800">{{ product.total_price | currency }}</div>
            </div>
        </div>
    </div>
</template>
<script>
import Labels from "@/libraries/labels";
import { __ } from '@/libraries/lang';
import { Popup } from '@/libraries/popup';
import nsPosConfirmPopupVue from '@/popups/ns-pos-confirm-popup.vue';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
export default {
    props: [ 'order' ],
    data() {
        return {
            processingStatuses,
            deliveryStatuses,
            labels: new Labels,
            showProcessingSelect: false,
            showDeliverySelect: false,
        }
    },
    mounted() {
    },
    methods: {
        __,
        submitProcessingChange() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Would you proceed ?' ),
                message: __( 'The processing status of the order will be changed. Please confirm your action.' ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( `/api/nexopos/v4/orders/${this.order.id}/processing`, {
                            process_status: this.order.process_status
                        }).subscribe( result => {
                            this.showProcessingSelect   =   false;
                            nsSnackBar.success( result.message ).subscribe();
                        }, ( error ) => {
                            nsSnackBar.error( error.message || __( 'Unexpected error occured.' ) ).subscribe();
                        })                            
                    }
                }
            })
        },

        submitDeliveryStatus() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Would you proceed ?' ),
                message: __( 'The delivery status of the order will be changed. Please confirm your action.' ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( `/api/nexopos/v4/orders/${this.order.id}/delivery`, {
                            delivery_status: this.order.delivery_status
                        }).subscribe( result => {
                            this.showDeliverySelect     =   false;
                            nsSnackBar.success( result.message ).subscribe();
                        }, ( error ) => {
                            nsSnackBar.error( error.message || __( 'Unexpected error occured.' ) ).subscribe();
                        })                            
                    }
                }
            })
        }
    }
}
</script>