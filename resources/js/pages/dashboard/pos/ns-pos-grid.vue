<template>
    <div id="pos-grid" class="rounded shadow bg-white overflow-hidden flex-auto">
        <div id="grid-header" class="p-2 border-b border-gray-200">
            <div class="border rounded flex border-gray-400 overflow-hidden">
                <button class="w-10 h-10 bg-gray-200 border-r border-gray-400">S</button>
                <button class="w-10 h-10 shadow-inner bg-gray-300 border-r border-gray-400">S</button>
                <input type="text" class="flex-auto outline-none px-2 bg-gray-100">
            </div>
        </div>
        <div id="grid-breadscrumb" class="p-2 border-gray-200">
            <ul class="flex">
                <li><a href="javascript:void(0)" class="px-3 text-gray-700">Home > </a></li>
                <li><a href="javascript:void(0)" class="px-3 text-gray-700">Mens > </a></li>
                <li><a href="javascript:void(0)" class="px-3 text-gray-700">Shirts > </a></li>
                <li><a href="javascript:void(0)" class="px-3 text-gray-700">Sport > </a></li>
            </ul>
        </div>
        <div id="grid-items" class="overflow-hidden flex-auto">
            <div class="grid grid-cols-6 gap-0 overflow-y-auto">
                
                <!-- Loop Products Or Categories -->

                <div class="border h-40 border-gray-200">1</div>
                
                <!-- End Loop -->

            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'ns-pos-grid'
}
</script>