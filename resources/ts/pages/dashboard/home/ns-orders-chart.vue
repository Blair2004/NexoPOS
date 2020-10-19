<template>
    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="head bg-indigo-400 flex-auto flex h-56">
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
    mounted() {
        console.log( 'foo' );
    }
}
</script>