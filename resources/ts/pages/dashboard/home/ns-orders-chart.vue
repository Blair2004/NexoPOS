<template>
    <div id="ns-orders-chart" class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="head flex-auto flex h-56">
            <div class="w-full h-full pt-2">
                <vue-apex-charts v-if="report" height="100%" type="area" :options="chartOptions" :series="series"></vue-apex-charts>
            </div>
        </div>
        <div class="foot p-2 -mx-4 flex flex-wrap">
            <div class="flex w-full md:w-1/2 lg:w-full xl:w-1/2 lg:border-b lg:border-t xl:border-none lg:py-1 lg:my-1">
                <div class="px-4 w-1/2 lg:w-1/2 flex flex-col items-center justify-center">
                    <span class="text-xs">{{ __( 'Weekly Sales' ) }}</span>
                    <h2 class="text-lg xl:text-xl font-bold">{{ totalWeeklySales | currency( 'abbreviate' ) }}</h2>
                </div>
                <div class="px-4 w-1/2 lg:w-1/2 flex flex-col items-center justify-center">
                    <span class="text-xs">{{ __( 'Week Taxes' ) }}</span>
                    <h2 class="text-lg xl:text-xl font-bold">{{ totalWeekTaxes | currency( 'abbreviate' ) }}</h2>
                </div>
            </div>
            <div class="flex w-full md:w-1/2 lg:w-full xl:w-1/2">
                <div class="px-4 w-full lg:w-1/2 flex flex-col items-center justify-center">
                    <span class="text-xs">{{ __( 'Net Income' ) }}</span>
                    <h2 class="text-lg xl:text-xl font-bold">{{ totalWeekIncome | currency( 'abbreviate' ) }}</h2>
                </div>
                <div class="px-4 w-full lg:w-1/2 flex flex-col items-center justify-center">
                    <span class="text-xs">{{ __( 'Week Expenses' ) }}</span>
                    <h2 class="text-lg xl:text-xl font-bold">{{ totalWeekExpenses | currency( 'abbreviate' ) }}</h2>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang';
export default {
    name: 'ns-orders-chart',
    data() {
        return {
            totalWeeklySales: 0,
            totalWeekTaxes: 0,
            totalWeekExpenses: 0,
            totalWeekIncome: 0,
            chartOptions: {
                theme: {
                    mode: window.ns.theme
                },
                chart: {
                    id: 'vuechart-example',
                    width: '100%',
                    height: '100%',
                },
                stroke: {
                    curve: 'smooth',
                    dashArray: [ 0, 8 ]
                },
                xaxis: {
                    categories: []
                },
                colors: [
                    '#5f83f3', '#AAA'
                ],
            },
            series: [{
                name: __( 'Current Week' ),
                data: []
            },{
                name: __( 'Previous Week' ),
                data: []
            }],
            reportSubscription: null,
            report: null
        }
    },
    methods: {
        __
    },
    mounted() {
        this.reportSubscription     =   Dashboard.weeksSummary.subscribe( data => {
            if ( data.result !== undefined ) {
                this.chartOptions.xaxis.categories  =   data.result.map( r => r.label );
                this.report             =   data;
                this.totalWeeklySales   =   0;
                this.totalWeekIncome    =   0;
                this.totalWeekExpenses  =   0;
                this.totalWeekTaxes     =   0;

                this.report.result.forEach( ( result, index ) => {
                    /**
                     * current week
                     */
                    if ( result.current !== undefined ) {
                        const sales     =   result.current.entries.map( dashboardDay => dashboardDay.day_paid_orders );
                        let total       =   0;

                        if ( sales.length > 0 ) {
                            total       =   sales.reduce( ( b, a ) => b + a );
                        }

                        /**
                         * this compute the week expenses
                         * and taxes for the current week
                         */
                        this.totalWeekExpenses  +=  result.current.entries.map( dashboardDay => parseFloat( dashboardDay.day_expenses ) ).reduce( ( b, a ) => b + a );
                        this.totalWeekTaxes     +=  result.current.entries.map( dashboardDay => parseFloat( dashboardDay.day_taxes ) ).reduce( ( b, a ) => b + a );
                        this.totalWeekIncome    +=  result.current.entries.map( dashboardDay => parseFloat( dashboardDay.day_income ) ).reduce( ( b, a ) => b + a );

                        this.series[ 0 ].data.push( total );
                        
                    } else {
                        this.series[ 0 ].data.push(0);
                    }

                    /**
                     * previous week
                     */
                    if ( result.previous !== undefined ) {
                        const sales     =   result.previous.entries.map( dashboardDay => dashboardDay.day_paid_orders );
                        let total       =   0;

                        if ( sales.length > 0 ) {
                            total       =   sales.reduce( ( b, a ) => b + a );
                        }

                        this.series[ 1 ].data.push( total );
                    } else {
                        this.series[ 1 ].data.push(0);
                    }
                });

                this.totalWeeklySales   =   this.series[0].data.reduce( ( b, a ) => b + a );
            }
        });
    }
}
</script>