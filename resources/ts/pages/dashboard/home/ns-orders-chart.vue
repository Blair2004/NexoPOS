<template>
    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="head bg-indigo-400 flex-auto flex h-56">
            <div class="w-full h-full">
                <vue-apex-charts height="100%" type="bar" :options="chartOptions" :series="series"></vue-apex-charts>
            </div>
        </div>
        <div class="p-2 bg-white -mx-4 flex flex-wrap">
            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                <span class="text-xs text-gray-600">Gross Income</span>
                <h2 class="text-3xl text-gray-700 font-bold">$ 725</h2>
            </div>
            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                <span class="text-xs text-gray-600">Week Taxes</span>
                <h2 class="text-3xl text-gray-700 font-bold">$ 125</h2>
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
export default {
    name: 'ns-orders-chart',
    data() {
        return {
            chartOptions: {
                chart: {
                    id: 'vuechart-example',
                    width: '100%',
                    height: '100%',
                },
                xaxis: {
                    categories: []
                },
                colors: [
                    '#333', '#EEE'
                ]
            },
            series: [{
                name: 'series-1',
                data: []
            }],
            reportSubscription: null,
            report: null
        }
    },
    mounted() {
        this.reportSubscription     =   Dashboard.weeksSummary.subscribe( data => {
            this.report     =   data;
            console.log( data );
            this.chartOptions.xaxis.categories  =   this.report.results.map( r => r.label );
        });
    }
}
</script>