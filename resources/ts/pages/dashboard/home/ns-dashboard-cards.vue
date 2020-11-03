<template>
    <div class="-m-4 flex flex-wrap">
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto rounded-lg shadow-lg bg-gradient-to-br from-blue-400 to-blue-600 text-white px-3 py-5">
                <div class="text-4xl font-black w-1/2 flex items-center justify-center">{{ ( report.total_paid_orders || 0 ) | abbreviate | currency }}</div>
                <div class="flex flex-col px-2 w-1/2 justify-center">
                    <h3 class="font-bold">Total Sales</h3>
                    <h4 class="text-xs font-semibold">+{{ ( report.day_paid_orders || 0 ) | abbreviate | currency }} Today</h4>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto rounded-lg shadow-lg bg-gradient-to-br from-green-400 to-green-600 text-white px-3 py-5">
                <div class="text-4xl font-black w-1/2 flex items-center justify-center">{{ ( report.total_partially_paid_orders + report.total_unpaid_orders || 0 ) | abbreviate | currency }}</div>
                <div class="flex flex-col px-2 w-1/2 justify-center">
                    <h3 class="font-bold">Incomplete Orders</h3>
                    <h4 class="text-xs font-semibold">+{{ ( report.day_unpaid_orders + report.day_partially_paid_orders || 0 ) | abbreviate | currency }} Today</h4>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto rounded-lg shadow-lg bg-gradient-to-br from-teal-400 to-teal-600 text-white px-3 py-5">
                <div class="text-4xl font-black w-1/2 flex items-center justify-center">{{ ( report.total_wasted_goods || 0 ) | abbreviate | currency }}</div>
                <div class="flex flex-col px-2 w-1/2 justify-center">
                    <h3 class="font-bold">Wasted Goods</h3>
                    <h4 class="text-xs font-semibold">+{{ ( report.day_wasted_goods || 0 ) | abbreviate | currency }} Today</h4>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto rounded-lg shadow-lg bg-gradient-to-br from-indigo-400 to-indigo-600 text-white px-3 py-5">
                <div class="text-4xl font-black w-1/2 flex items-center justify-center">{{ ( report.total_expenses || 0 ) | abbreviate | currency }}</div>
                <div class="flex flex-col px-2 w-1/2 justify-center">
                    <h3 class="font-bold">Expenses</h3>
                    <h4 class="text-xs font-semibold">+{{ ( report.day_expenses || 0 ) | abbreviate | currency }} Last Month</h4>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '@/bootstrap';
export default {
    name : 'ns-dashboard-cards',
    data() {
        return {
            report: {}
        }
    },
    mounted() {
        this.loadReport();
    },
    methods: {
        loadReport() {
            nsHttpClient.get( '/api/nexopos/v4/dashboard/day' )
                .subscribe( result => {
                    this.report    =   result;
                })
        }
    }
}
</script>