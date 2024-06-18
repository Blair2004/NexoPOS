<template>
    <div id="ns-orders-summary" class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="p-2 flex title items-center justify-between border-b">
            <h3 class="font-semibold">{{ __( 'Recents Orders' ) }}</h3>
            <div>
                <ns-close-button @click="$emit( 'onRemove' )"></ns-close-button>
            </div>
        </div>
        <div class="head flex-auto flex-col flex h-64 overflow-y-auto ns-scrollbar">
            <div class="h-full flex items-center justify-center" v-if="! hasLoaded">
                <ns-spinner size="8" border="4"></ns-spinner>
            </div>
            <div class="h-full flex items-center justify-center flex-col" v-if="hasLoaded && orders.length === 0">
                <i class="las la-grin-beam-sweat text-6xl"></i>
                <p class="text-sm text-center">{{ __( 'Well.. nothing to show for the meantime.' ) }}</p>
            </div>
            <div
                v-for="order of orders"
                :key="order.id"
                :class="order.payment_status === 'paid' ? 'paid-order' : 'other-order'"
                class="border-b single-order p-2 flex justify-between">
                <div>
                    <h3 class="text-lg font-semibold">{{ __( 'Order' ) }} : {{ order.code }}</h3>
                    <div class="flex -mx-2">
                        <div class="px-1">
                            <h4 class="text-semibold text-xs">
                                <i class="lar la-user-circle"></i>
                                <span>{{ order.user.username }}</span>
                            </h4>
                        </div>
                        <div class="divide-y-4"></div>
                        <div class="px-1">
                            <h4 class="text-semibold text-xs">
                                <i class="las la-clock"></i>
                                <span>{{ order.created_at }}</span>
                            </h4>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 
                        :class="order.payment_status === 'paid' ? 'paid-currency' : 'unpaid-currency'" 
                        class="text-xl font-bold">{{ nsCurrency( order.total ) }}</h2>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '~/bootstrap';
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-orders-summary',
    data() {
        return {
            orders: [],
            subscription: null,
            hasLoaded: false,
        }
    },
    mounted() {
        this.hasLoaded      =   false;
        this.subscription   =   Dashboard.recentOrders.subscribe( orders => {
            this.hasLoaded  =   true;
            this.orders     =   orders;
        });
    },
    methods: { __, nsCurrency },
    unmounted() {
        this.subscription.unsubscribe();
    }
}
</script>
