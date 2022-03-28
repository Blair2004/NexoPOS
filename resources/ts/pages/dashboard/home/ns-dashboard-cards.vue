<template>
    <div class="-m-4 flex flex-wrap" id="dashboard-cards">
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-info-secondary to-info-tertiary px-3 py-5">
                <div class="flex flex-row md:flex-col flex-auto">
                    <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                        <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Total Sales' ) }}</h6>
                        <h3 class="text-2xl font-black">
                            {{ ( report.total_paid_orders || 0 ) | currency( 'abbreviate' ) }}
                        </h3>
                    </div>
                    <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                        <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Total Sales' ) }}</h6>
                        <h4 class="text-xs text-right">+{{ ( report.day_paid_orders || 0 ) | currency }} {{ __( 'Today' ) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-green-400 to-green-600 px-3 py-5">
                <div class="flex flex-row md:flex-col flex-auto">
                    <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                        <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Incomplete Orders' ) }}</h6>
                        <h3 class="text-2xl font-black">
                            {{ ( report.total_partially_paid_orders + report.total_unpaid_orders || 0 ) | currency( 'abbreviate' ) }}
                        </h3>
                    </div>
                    <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                        <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Incomplete Orders' ) }}</h6>
                        <h4 class="text-xs text-right">+{{ ( report.day_unpaid_orders + report.day_partially_paid_orders || 0 ) | currency }} {{ __( 'Today' ) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-red-300 via-red-400 to-red-500 px-3 py-5">
                <div class="flex flex-row md:flex-col flex-auto">
                    <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                        <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Wasted Goods' ) }}</h6>
                        <h3 class="text-2xl font-black">
                            {{ ( report.total_wasted_goods || 0 ) | currency( 'abbreviate' ) }}
                        </h3>
                    </div>
                    <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                        <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Wasted Goods' ) }}</h6>
                        <h4 class="text-xs text-right">+{{ ( report.day_wasted_goods || 0 ) | currency }} {{ __( 'Today' ) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 w-full md:w-1/2 lg:w-1/4">
            <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-indigo-400 to-indigo-600 px-3 py-5">
                <div class="flex flex-row md:flex-col flex-auto">
                    <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                        <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Expenses' ) }}</h6>
                        <h3 class="text-2xl font-black">
                            {{ ( report.total_expenses || 0 ) | currency( 'abbreviate' ) }}
                        </h3>
                    </div>
                    <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                        <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Expenses' ) }}</h6>
                        <h4 class="text-xs text-right">+{{ ( report.day_expenses || 0 ) | currency }} {{ __( 'Today' ) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '@/bootstrap';
import { __ } from '@/libraries/lang';
export default {
    name : 'ns-dashboard-cards',
    data() {
        return {
            report: {}
        }
    },
    mounted() {
        this.loadReport();
        console.log( nsLanguage.getEntries() );
    },
    methods: {
        __,
        loadReport() {
            nsHttpClient.get( '/api/nexopos/v4/dashboard/day' )
                .subscribe( result => {
                    this.report    =   result;
                })
        }
    }
}
</script>