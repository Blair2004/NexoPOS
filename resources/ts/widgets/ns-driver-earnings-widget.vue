<template>
    <div class="ns-box rounded shadow">
        <div class="ns-box-header border-b flex justify-between items-center p-2">
            <h3>{{ __('Driver Earnings Stats') }}</h3>
            <div class="flex -mx-1">
                <div class="px-1">
                    <ns-icon-button class="widget-handle las la-arrows-alt"></ns-icon-button>
                </div>
                <div class="px-1">
                    <ns-close-button @click="$emit('onRemove')" />
                </div>
            </div>
        </div>
        <div class="ns-box-body p-4">
            <!-- Loading state -->
            <div v-if="isLoading" class="flex justify-center items-center py-8">
                <ns-spinner></ns-spinner>
            </div>
            
            <!-- Error state -->
            <div v-else-if="error" class="text-red-500 text-center py-4">
                <p>{{ error }}</p>
                <button @click="loadDriverEarnings" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    {{ __('Retry') }}
                </button>
            </div>
            
            <!-- Data display -->
            <div v-else class="space-y-4">
                <!-- Current Month Stats -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-success-primary p-3 rounded">
                        <h4 class="text-sm font-medium text-primary-secondary">{{ __('This Month - Approved') }}</h4>
                        <p class="text-xl font-bold text-success-tertiary">{{ formatCurrency(stats.this_month?.paid_earnings || 0) }}</p>
                        <p class="text-xs text-success-tertiary">{{ stats.this_month?.total_deliveries || 0 }} {{ __('deliveries') }}</p>
                    </div>

                    <div class="bg-warning-primary p-3 rounded">
                        <h4 class="text-sm font-medium text-warning-secondary">{{ __('This Month - Pending') }}</h4>
                        <p class="text-xl font-bold text-warning-tertiary">{{ formatCurrency(stats.this_month?.pending_earnings || 0) }}</p>
                        <p class="text-xs text-warning-secondary">{{ __('Awaiting approval') }}</p>
                    </div>
                </div>
                
                <!-- Today's Stats -->
                <div class="border-t border-box-edge pt-4">
                    <h4 class="text-sm font-medium text-gray-600 mb-2">{{ __('Today') }}</h4>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div class="text-center">
                            <p class="font-semibold">{{ stats.today?.total_deliveries || 0 }}</p>
                            <p class="text-xs text-gray-500">{{ __('Deliveries') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="font-semibold">{{ formatCurrency(stats.today?.total_earnings || 0) }}</p>
                            <p class="text-xs text-gray-500">{{ __('Earnings') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="font-semibold text-green-600">{{ formatCurrency(stats.today?.paid_earnings || 0) }}</p>
                            <p class="text-xs text-gray-500">{{ __('Paid') }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Driver Status -->
                <div class="border-t border-box-edge pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('Status') }}</span>
                        <span :class="getStatusClass(stats.status)" class="px-2 py-1 text-xs rounded-full">
                            {{ getStatusLabel(stats.status) }}
                        </span>
                    </div>
                </div>
                
                <!-- All Time Summary -->
                <div class="border-t border-box-edge pt-4">
                    <h4 class="text-sm font-medium text-gray-600 mb-2">{{ __('All Time') }}</h4>
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <span>{{ __('Total Deliveries') }}</span>
                            <span class="font-medium">{{ stats.all_time?.total_deliveries || 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ __('Total Earnings') }}</span>
                            <span class="font-medium">{{ formatCurrency(stats.all_time?.total_earnings || 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
declare const __;
declare const nsHttpClient;
declare const nsCurrency;

export default {
    name: 'DriverEarningsWidgetComponent',
    data() {
        return {
            stats: {
                today: null,
                this_month: null,
                all_time: null,
                status: 'offline'
            } as any,
            isLoading: true,
            error: null,
            refreshInterval: null
        }
    },
    methods: {
        __,
        loadDriverEarnings() {
            this.isLoading = true;
            this.error = null;
            
            nsHttpClient.get('/api/drivers/earnings/stats')
                .subscribe({
                    next: (response) => {
                        this.stats = response.data;
                        this.isLoading = false;
                    },
                    error: (error) => {
                        this.error = error.message || __('Failed to load earnings data');
                        this.isLoading = false;
                        console.error('Error fetching driver earnings:', error);
                    }
                });
        },
        
        formatCurrency(amount) {
            return nsCurrency(amount);
        },
        
        getStatusClass(status) {
            const statusClasses = {
                'available': 'bg-green-100 text-green-800',
                'busy': 'bg-yellow-100 text-warning-primary',
                'offline': 'bg-gray-100 text-gray-800',
                'disabled': 'bg-red-100 text-red-800'
            };
            return statusClasses[status] || 'bg-gray-100 text-gray-800';
        },
        
        getStatusLabel(status) {
            const statusLabels = {
                'available': __('Available'),
                'busy': __('Busy'),
                'offline': __('Offline'),
                'disabled': __('Disabled')
            };
            return statusLabels[status] || __('Unknown');
        },
        
        startAutoRefresh() {
            // Refresh every 5 minutes
            this.refreshInterval = setInterval(() => {
                this.loadDriverEarnings();
            }, 300000);
        },
        
        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        }
    },
    
    mounted() {
        this.loadDriverEarnings();
        this.startAutoRefresh();
    },
    
    beforeUnmount() {
        this.stopAutoRefresh();
    }
}
</script>

<style scoped>
.ns-box {
    min-height: 200px;
}

.widget-handle {
    cursor: move;
}

/* Responsive adjustments for smaller screens */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .grid-cols-3 {
        grid-template-columns: 1fr;
    }
}
</style>
