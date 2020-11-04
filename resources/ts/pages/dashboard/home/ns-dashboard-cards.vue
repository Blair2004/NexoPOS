<template>
    <div class="-m-4 flex flex-wrap">
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-blue-400 to-blue-600 text-white px-3 py-5">
                <div class="flex flex-row lg:flex-col xl:flex-row flex-auto">
                    <div class="w-1/2 lg:w-full xl:w-1/2 flex lg:flex-col lg:items-start xl:flex-row xl:items-center justify-center">
                        <h6 class="font-bold hidden text-right lg:inline-block xl:hidden">Total Sales</h6>
                        <h3 class="text-4xl font-black">
                            {{ ( report.total_paid_orders || 0 ) | currency( 'abbreviate' ) }}
                        </h3>
                    </div>
                    <div class="w-1/2 lg:w-full xl:w-1/2 flex flex-col px-2 justify-center items-end">
                        <h6 class="font-bold inline-block text-right lg:hidden xl:inline-block">Total Sales</h6>
                        <h4 class="text-xs text-right">+{{ ( report.day_paid_orders || 0 ) | currency }} Today</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-green-400 to-green-600 text-white px-3 py-5">
                <div class="flex flex-row lg:flex-col xl:flex-row flex-auto">
                    <div class="w-1/2 lg:w-full xl:w-1/2 flex lg:flex-col lg:items-start xl:flex-row xl:items-center justify-center">
                        <h6 class="font-bold hidden text-right lg:inline-block xl:hidden">Incomplete Orders</h6>
                        <h3 class="text-4xl font-black">
                            {{ ( report.total_partially_paid_orders + report.total_unpaid_orders || 0 ) | currency( 'abbreviate' ) }}
                        </h3>
                    </div>
                    <div class="w-1/2 lg:w-full xl:w-1/2 flex flex-col px-2 justify-center items-end">
                        <h6 class="font-bold inline-block text-right lg:hidden xl:inline-block">Incomplete Orders</h6>
                        <h4 class="text-xs text-right">+{{ ( report.day_unpaid_orders + report.day_partially_paid_orders || 0 ) | currency }} Today</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-teal-400 to-teal-600 text-white px-3 py-5">
                <div class="flex flex-row lg:flex-col xl:flex-row flex-auto">
                    <div class="w-1/2 lg:w-full xl:w-1/2 flex lg:flex-col lg:items-start xl:flex-row xl:items-center justify-center">
                        <h6 class="font-bold hidden text-right lg:inline-block xl:hidden">Wasted Goods</h6>
                        <h3 class="text-4xl font-black">
                            {{ ( report.total_wasted_goods || 0 ) | currency( 'abbreviate' ) }}
                        </h3>
                    </div>
                    <div class="w-1/2 lg:w-full xl:w-1/2 flex flex-col px-2 justify-center items-end">
                        <h6 class="font-bold inline-block text-right lg:hidden xl:inline-block">Wasted Goods</h6>
                        <h4 class="text-xs text-right">+{{ ( report.day_wasted_goods || 0 ) | currency }} Today</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-indigo-400 to-indigo-600 text-white px-3 py-5">
                <div class="flex flex-row lg:flex-col xl:flex-row flex-auto">
                    <div class="w-1/2 lg:w-full xl:w-1/2 flex lg:flex-col lg:items-start xl:flex-row xl:items-center justify-center">
                        <h6 class="font-bold hidden text-right lg:inline-block xl:hidden">Expenses</h6>
                        <h3 class="text-4xl font-black">
                            {{ ( report.total_expenses || 0 ) | currency( 'abbreviate' ) }}
                        </h3>
                    </div>
                    <div class="w-1/2 lg:w-full xl:w-1/2 flex flex-col px-2 justify-center items-end">
                        <h6 class="font-bold inline-block text-right lg:hidden xl:inline-block">Expenses</h6>
                        <h4 class="text-xs text-right">+{{ ( report.day_expenses || 0 ) | currency }} Today</h4>
                    </div>
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