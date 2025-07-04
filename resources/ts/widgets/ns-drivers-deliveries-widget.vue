<template>
    <div class="ns-box rounded shadow">
        <div class="ns-box-header border-b flex justify-between items-center p-2">
            <h3>{{ __('Latest Pending Deliveries') }}</h3>
            <div class="flex -mx-1">
                <div class="px-1">
                    <ns-icon-button class="widget-handle las la-arrows-alt"></ns-icon-button>
                </div>
                <div class="px-1">
                    <ns-close-button @click="$emit('onRemove')" />
                </div>
            </div>
        </div>
        <div class="ns-box-body p-2">
            <div v-if="isLoading" class="flex items-center justify-center py-4">
                <ns-spinner />
            </div>
            <div v-else>
                <div v-if="deliveries.length === 0" class="text-center text-gray-500 py-4">
                    {{ __('No pending deliveries found.') }}
                </div>
                <ul v-else>
                    <li v-for="delivery in deliveries" :key="delivery.id" class="border-b py-2 last:border-b-0">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">#{{ delivery.code || delivery.id }}</span>
                            <span class="text-xs text-gray-400">{{ delivery.created_at }}</span>
                        </div>
                        <div class="text-sm text-gray-700">{{ __( 'Customer:' ) }} {{ getCustomerFullName( delivery.customer ) }}</div>
                        <div class="text-xs text-gray-500">{{ __('Status:') }} {{ delivery.delivery_status || '' }}</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
declare const __, nsHttpClient, ns;
import { ref, onMounted, toRefs } from 'vue';
export default {
    name: 'DriversDeliveryWidget',
    props: {
        driverId: {
            type: [String, Number],
            required: false,
            default: () => (typeof ns !== 'undefined' && ns.user ? ns.user.attributes.user_id : null)
        }
    },
    methods: {
        getCustomerFullName(customer) {
            if (!customer) return '';
            return customer.first_name && customer.last_name
                ? `${customer.first_name} ${customer.last_name}`
                : customer.username || '';
        },
    },
    setup(props) {
        const { driverId } = toRefs(props);
        const deliveries = ref([]);
        const isLoading = ref(true);

        onMounted(async () => {
            if (!driverId.value) {
                isLoading.value = false;
                return;
            }
            try {
                const response = await nsHttpClient.get(`/api/drivers/${driverId.value}/latest-deliveries`).toPromise();
                deliveries.value = response.data || response;
            } catch (e) {
                deliveries.value = [];
            }
            isLoading.value = false;
        });

        return { deliveries, isLoading, __ };
    }
}
</script>
