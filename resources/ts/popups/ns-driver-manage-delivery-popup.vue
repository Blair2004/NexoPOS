<template>
    <div class="ns-box w-[95vw] md:w-[60vw] lg:w-[40vw] xl:w-[30vw] shadow flex flex-col">
        <div class="p-2 flex justify-between items-center ns-box-header border-b">
            <h3>{{ __( 'Manage Delivery: {name}' ).replace( '{name}', order.code ) }}</h3>
            <div>
                <ns-close-button @click="popupResolver( false )"></ns-close-button>
            </div>
        </div>
        <div class="ns-box-body p-4">
            <div class="py-4 flex justify-center items-center" v-if="isLoading">
                <ns-spinner></ns-spinner>
            </div>
            <div v-else>
                <!-- Confirmation Messages -->
                <div v-if="showStartConfirmation" class="bg-success-secondary text-white p-3 rounded mb-4">
                    <h4 class="font-semibold">{{ __( 'Start Delivery Confirmation' ) }}</h4>
                    <p>{{ __( 'Are you sure you want to start this delivery? This will change the delivery status from pending to ongoing.' ) }}</p>
                </div>
                <div v-if="showRejectConfirmation" class="bg-error-secondary text-white p-3 rounded mb-4">
                    <h4 class="font-semibold">{{ __( 'Reject Delivery Confirmation' ) }}</h4>
                    <p>{{ __( 'Are you sure you want to reject this delivery? This will unassign you from the order and make it available for other drivers.' ) }}</p>
                </div>

                <ns-tabs :active="activeTab" @active="setActiveTab( $event )">
                    <ns-tabs-item 
                        :label="__( 'Order & Customer' )" 
                        padding="p-3"
                        identifier="order-customer">
                        <div class="space-y-4">
                            <div class="bg-info-secondary text-white p-3 rounded">
                                <h4 class="font-semibold mb-2">{{ __( 'Order Details' ) }}</h4>
                                <div class="space-y-1">
                                    <p><strong>{{ __( 'Code:' ) }}</strong> {{ order.code }}</p>
                                    <p><strong>{{ __( 'Total:' ) }}</strong> {{ nsCurrency( order.total ) }}</p>
                                    <p><strong>{{ __( 'Payment Status:' ) }}</strong> {{ order.payment_status || 'N/A' }}</p>
                                    <p><strong>{{ __( 'Date:' ) }}</strong> {{ order.created_at ? new Date( order.created_at ).toLocaleDateString() : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-100 p-3 rounded" v-if="fullOrder?.customer">
                                <h4 class="font-semibold mb-2 text-gray-800">{{ __( 'Customer Information' ) }}</h4>
                                <div class="space-y-1 text-gray-700">
                                    <p><strong>{{ __( 'Name:' ) }}</strong> {{ fullOrder.customer.first_name }} {{ fullOrder.customer.last_name }}</p>
                                    <p v-if="fullOrder.customer.email"><strong>{{ __( 'Email:' ) }}</strong> {{ fullOrder.customer.email }}</p>
                                    <p v-if="fullOrder.customer.phone"><strong>{{ __( 'Phone:' ) }}</strong> {{ fullOrder.customer.phone }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-100 p-3 rounded" v-else-if="loadingFullOrder">
                                <div class="flex items-center justify-center py-4">
                                    <ns-spinner size="8"></ns-spinner>
                                    <span class="ml-2 text-gray-600">{{ __( 'Loading customer information...' ) }}</span>
                                </div>
                            </div>
                            <div class="bg-gray-100 p-3 rounded" v-else>
                                <p class="text-gray-600">{{ __( 'No customer information available' ) }}</p>
                            </div>
                        </div>
                    </ns-tabs-item>
                    <ns-tabs-item 
                        :label="__( 'Delivery Address' )" 
                        padding="p-3"
                        identifier="delivery-address">
                        <div class="bg-blue-50 p-3 rounded">
                            <h4 class="font-semibold mb-2 text-blue-800">{{ __( 'Delivery Information' ) }}</h4>
                            <div class="space-y-2 text-blue-700" v-if="fullOrder?.shipping_address">
                                <p><strong>{{ __( 'Address:' ) }}</strong> {{ fullOrder.shipping_address.address_1 }}</p>
                                <p v-if="fullOrder.shipping_address.address_2"><strong>{{ __( 'Address 2:' ) }}</strong> {{ fullOrder.shipping_address.address_2 }}</p>
                                <p><strong>{{ __( 'City:' ) }}</strong> {{ fullOrder.shipping_address.city || 'N/A' }}</p>
                                <p><strong>{{ __( 'State:' ) }}</strong> {{ fullOrder.shipping_address.state || 'N/A' }}</p>
                                <p><strong>{{ __( 'Postal Code:' ) }}</strong> {{ fullOrder.shipping_address.postal_code || 'N/A' }}</p>
                                <p><strong>{{ __( 'Country:' ) }}</strong> {{ fullOrder.shipping_address.country || 'N/A' }}</p>
                                <p v-if="fullOrder.shipping_address.phone"><strong>{{ __( 'Contact Phone:' ) }}</strong> {{ fullOrder.shipping_address.phone }}</p>
                            </div>
                            <div v-else-if="loadingFullOrder" class="flex items-center justify-center py-4">
                                <ns-spinner size="8"></ns-spinner>
                                <span class="ml-2 text-blue-600">{{ __( 'Loading delivery address...' ) }}</span>
                            </div>
                            <div v-else>
                                <p class="text-blue-600">{{ __( 'No delivery address information available' ) }}</p>
                            </div>
                        </div>
                    </ns-tabs-item>
                </ns-tabs>
            </div>
        </div>
        <div class="border-t ns-box-footer p-2">
            <!-- Normal state buttons -->
            <div v-if="!showStartConfirmation && !showRejectConfirmation" class="flex justify-between">
                <ns-button @click="popupResolver( false )" type="default">{{ __( 'Cancel' ) }}</ns-button>
                <div class="flex space-x-2">
                    <ns-button @click="showRejectConfirmation = true" type="error" :disabled="isLoading">{{ __( 'Reject Delivery' ) }}</ns-button>
                    <ns-button @click="showStartConfirmation = true" type="success" :disabled="isLoading">{{ __( 'Start Delivery' ) }}</ns-button>
                </div>
            </div>
            
            <!-- Start confirmation buttons -->
            <div v-if="showStartConfirmation" class="flex justify-between">
                <ns-button @click="showStartConfirmation = false" type="default">{{ __( 'Cancel' ) }}</ns-button>
                <ns-button @click="confirmStartDelivery()" type="success" :disabled="isLoading">{{ __( 'Confirm Start' ) }}</ns-button>
            </div>
            
            <!-- Reject confirmation buttons -->
            <div v-if="showRejectConfirmation" class="flex justify-between">
                <ns-button @click="showRejectConfirmation = false" type="default">{{ __( 'Cancel' ) }}</ns-button>
                <ns-button @click="confirmRejectDelivery()" type="error" :disabled="isLoading">{{ __( 'Confirm Reject' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';
import { nsCurrency } from '~/filters/currency';

declare const nsHttpClient: any;
declare const nsSnackBar: any;

export default {
    name: 'NsDriverManageDeliveryPopup',
    methods: {
        __,
        popupResolver,
        popupCloser,
        nsCurrency,
        setActiveTab(tab: string) {
            this.activeTab = tab;
            // Load full order data when switching to tabs that need it
            if ((tab === 'order-customer' || tab === 'delivery-address') && !this.fullOrder && !this.loadingFullOrder) {
                this.loadFullOrderData();
            }
        },
        loadFullOrderData() {
            if (this.loadingFullOrder || this.fullOrder) {
                return;
            }
            
            this.loadingFullOrder = true;
            
            nsHttpClient.get(`/api/orders/${this.order.id}`)
                .subscribe({
                    next: (response) => {
                        this.fullOrder = response;
                        this.loadingFullOrder = false;
                    },
                    error: (error) => {
                        this.loadingFullOrder = false;
                        console.error('Failed to load full order data:', error);
                        nsSnackBar.error(error.message || __('Unable to load order details. Please try again.'));
                    }
                });
        },
        confirmStartDelivery() {
            this.isLoading = true;
            
            nsHttpClient.post(`/api/drivers/orders/${this.order.id}/start`, {})
                .subscribe({
                    next: (response) => {
                        this.isLoading = false;
                        nsSnackBar.success(response.message);
                        this.popupResolver(true);
                        if (this.component) {
                            this.component.$emit('reload');
                        }
                    },
                    error: (error) => {
                        this.isLoading = false;
                        nsSnackBar.error(error.message || __('Unable to start delivery. Please try again.'));
                    }
                });
        },
        confirmRejectDelivery() {
            this.isLoading = true;
            
            nsHttpClient.post(`/api/drivers/orders/${this.order.id}/reject`, {})
                .subscribe({
                    next: (response) => {
                        this.isLoading = false;
                        nsSnackBar.success(response.message);
                        this.popupResolver(true);
                        if (this.component) {
                            this.component.$emit('reload');
                        }
                    },
                    error: (error) => {
                        this.isLoading = false;
                        nsSnackBar.error(error.message || __('Unable to reject delivery. Please try again.'));
                    }
                });
        }
    },
    data() {
        return {
            isLoading: false,
            activeTab: 'order-customer',
            fullOrder: null,
            loadingFullOrder: false,
            showStartConfirmation: false,
            showRejectConfirmation: false,
        }
    },
    mounted() {
        this.popupCloser();
        // Load full order data on mount since we start with the order-customer tab
        this.loadFullOrderData();
    },
    props: ['order', 'popup', 'component'],
}
</script>
