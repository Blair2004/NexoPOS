<template>
    <div class="h-full bg-gray-200 flex flex-col">
    <div class="h-12 flex-shrink-0"></div>
    <div class="flex-auto overflow-hidden flex p-2">
        <div class="flex flex-auto overflow-hidden -m-2">
            <div class="w-1/2 flex overflow-hidden p-2">
                <div id="pos-cart" class="rounded shadow bg-white flex-auto flex overflow-hidden">
                    <div class="cart-table flex flex-auto flex-col overflow-hidden">
                        <div class="w-full text-gray-700 font-semibold flex">
                            <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200 bg-gray-100">{{ __( 'Product' ) }}</div>
                            <div class="w-1/6 p-2 border-b border-t-0 border-gray-200 bg-gray-100">{{ __( 'Qty' ) }}</div>
                            <div class="w-1/6 p-2 border border-r-0 border-t-0 border-gray-200 bg-gray-100">{{ __( 'Total' ) }}</div>
                        </div>
                        <div class="flex flex-auto flex-col overflow-auto">
                            <div class="text-gray-700 flex" v-for="( product, index ) of pos.products" :key="index">
                                <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200">
                                    <h3 class="font-semibold">{{ product.name }}</h3>
                                    <div class="-mx-1 flex">
                                        <div class="px-1">
                                            <a class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Price : {{ product.sale_price | 'currency' }}</a>
                                        </div>
                                        <div class="px-1"> 
                                            <a class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Discount 5% : $10</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-1/6 p-2 border-b border-gray-200 flex items-center justify-center cursor-pointer hover:bg-blue-100">
                                    <span class="border-b border-dashed border-blue-400 p-2">12</span>
                                </div>
                                <div class="w-1/6 p-2 border border-r-0 border-t-0 border-gray-200 flex items-center justify-center">$20</div>
                            </div>
                        </div>
                        <div class="h-16 flex flex-shrink-0 border-t border-gray-200">
                            <div class="flex items-center font-bold cursor-pointer justify-center bg-green-500 text-white hover:bg-green-600 border-r border-green-600 flex-auto">Pay</div>
                            <div class="flex items-center font-bold cursor-pointer justify-center bg-blue-500 text-white border-r hover:bg-blue-600 border-blue-600 flex-auto">Hold</div>
                            <div class="flex items-center font-bold cursor-pointer justify-center bg-white border-r border-gray-200 hover:bg-indigo-100 flex-auto">Discount</div>
                            <div class="flex items-center font-bold cursor-pointer justify-center bg-red-500 text-white border-gray-200 hover:bg-red-600 flex-auto">Void</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-1/2 p-2 flex overflow-hidden">
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
                            <div class="border h-40 border-gray-200">{{ $i }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
