<template>
    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="p-2 flex justify-between bg-white border-b">
            <h3 class="font-semibold text-gray-700">Recents Orders</h3>
            <div class="">
                
            </div>
        </div>
        <div class="head bg-gray-100 flex-auto flex-col flex h-56 overflow-y-auto">
            <div class="h-full flex items-center justify-center" v-if="! hasLoaded">
                <ns-spinner size="8" border="4"></ns-spinner>
            </div>
            <div class="h-full flex items-center justify-center flex-col" v-if="hasLoaded && orders.length === 0">
                <i class="las la-grin-beam-sweat text-6xl text-gray-700"></i>
                <p class="text-gray-600 text-sm">Well.. nothing to show for the meantime.</p>
            </div>
            <div 
                v-for="order of orders" 
                :key="order.id" 
                :class="order.payment_status === 'paid' ? 'bg-green-50' : 'bg-white'" 
                class="border-b border-gray-200 p-2 flex justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-600">Order : {{ order.code }}</h3>
                    <div class="flex -mx-2">
                        <div class="px-1">
                            <h4 class="text-semibold text-xs text-gray-500">
                                <i class="lar la-user-circle"></i>
                                <span>{{ order.user.username }}</span>
                            </h4>
                        </div>
                        <div class="divide-y-4"></div>
                        <div class="px-1">
                            <h4 class="text-semibold text-xs text-gray-600">
                                <i class="las la-clock"></i> 
                                <span>{{ order.created_at }}</span>
                            </h4>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 
                        :class="order.payment_status === 'paid' ? 'text-green-600' : 'text-gray-700'" 
                        class="text-xl font-bold">{{ order.total | currency }}</h2>
                </div>
            </div>
        </div>
        <!-- <div class="p-2 bg-white -mx-4 flex flex-wrap">
            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                <span class="text-xs text-gray-600">Week Due</span>
                <h2 class="text-3xl text-gray-700 font-bold">$ 35</h2>
            </div>
            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                <span class="text-xs text-gray-600">Partially</span>
                <h2 class="text-3xl text-gray-700 font-bold">$ 354</h2>
            </div>
            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                <span class="text-xs text-gray-600">Net Income</span>
                <h2 class="text-3xl text-gray-700 font-bold">$ 600</h2>
            </div>
            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                <span class="text-xs text-gray-600">Week Expenses</span>
                <h2 class="text-3xl text-gray-700 font-bold">$ 200</h2>
            </div>
        </div> -->
    </div>
</template>
<script>
import { nsHttpClient } from '@/bootstrap';
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
    destroyed() {
        this.subscription.unsubscribe();
    }
}
</script>