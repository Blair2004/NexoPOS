<template>
    <div class="h-full bg-gray-300 flex flex-col" id="pos-container">
        <div class="h-12 flex-shrink-0 px-2 pt-2 flex">
            <div class="-mx-2 flex">
                <div class="flex px-2">
                    <button class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
                        <i class="mr-1 text-xl las la-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </button>
                </div>
                <div class="flex px-2">
                    <button class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
                        <i class="mr-1 text-xl lar la-hand-pointer"></i>
                        <span>Pending Orders</span>
                    </button>
                </div>
                <div class="flex px-2">
                    <button class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
                        <i class="mr-1 text-xl las la-truck"></i>
                        <span> Delivery</span>
                    </button>
                </div>
                <div class="flex px-2">
                    <button class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
                        <i class="mr-1 text-xl lar la-user-circle"></i>
                        <span>Customers</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="flex-auto overflow-hidden flex p-2">
            <div class="flex flex-auto overflow-hidden -m-2">
                <div class="w-1/2 flex overflow-hidden p-2">
                    <ns-pos-cart></ns-pos-cart>
                </div>
                <div class="w-1/2 p-2 flex overflow-hidden">
                    <ns-pos-grid></ns-pos-grid>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import NsPosCart from './ns-pos-cart';
import NsPosGrid from './ns-pos-grid.vue';

export default {
    name: 'ns-pos',
    components: {
        NsPosCart,
        NsPosGrid
    }
}
</script>