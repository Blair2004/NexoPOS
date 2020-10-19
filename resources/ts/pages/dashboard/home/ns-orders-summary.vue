<template>
    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="p-2 flex justify-between bg-white border-b">
            <h3 class="font-semibold text-gray-700">Recents Orders</h3>
            <div class="">
                
            </div>
        </div>
        <div class="head bg-gray-200 flex-auto flex h-56">
            <table class="table flex-auto">
                <thead>
                    <tr>
                        <th width="100" class="px-3 py-1 bg-white border-t-0 border-l-0 border-gray-300 border">Order</th>
                        <th width="100" class="px-3 py-1 bg-white border-t-0 border-gray-300 border">Total</th>
                        <th width="100" class="px-3 py-1 bg-white border-t-0 border-r-0 border-gray-300 border">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order of orders" :key="order.id">
                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">{{ order.code }}</td>
                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">{{ order.total | currency }}</td>
                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">{{ order.created_at }}</td>
                    </tr>
                    <tr>
                        <td v-if="orders.length === 0" class="text-gray-600 p-1 text-center bg-white" colspan="3">No order to display...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="p-2 bg-white -mx-4 flex flex-wrap">
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
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '@/bootstrap';
export default {
    name: 'ns-orders-summary',
    data() {
        return {
            orders: [],
            subscription: null
        }
    },
    mounted() {
        this.subscription   =   Dashboard.recentOrders.subscribe( orders => {
            this.orders     =   orders;
        });
    },
    destroyed() {
        this.subscription.unsubscribe();
    }
}
</script>