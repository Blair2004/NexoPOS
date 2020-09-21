<template>
    <div id="pos-cart" class="rounded shadow bg-white flex-auto flex overflow-hidden">
        <div class="cart-table flex flex-auto flex-col overflow-hidden">
            <div class="w-full text-gray-700 font-semibold flex">
                <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200 bg-gray-100">Product</div>
                <div class="w-1/6 p-2 border-b border-t-0 border-gray-200 bg-gray-100">Quantity</div>
                <div class="w-1/6 p-2 border border-r-0 border-t-0 border-gray-200 bg-gray-100">Total</div>
            </div>
            <div class="flex flex-auto flex-col overflow-auto">
                
                <!-- Loop Procuts On Cart -->

                <div class="text-gray-700 flex">
                    <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200">
                        <h3 class="font-semibold">Some Product</h3>
                        <div class="-mx-1 flex">
                            <div class="px-1">
                                <a class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Price : $25</a>
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
                
                <!-- End Loop -->

            </div>
            <div class="flex">
                <table class="table w-full text-sm text-gray-700">
                    <tr>
                        <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                        <td width="200" class="border border-gray-400 p-2">Sub Total</td>
                        <td width="200" class="border border-gray-400 p-2"></td>
                    </tr>
                    <tr>
                        <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                        <td width="200" class="border border-gray-400 p-2">Discount</td>
                        <td width="200" class="border border-gray-400 p-2"></td>
                    </tr>
                    <tr>
                        <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                        <td width="200" class="border border-gray-400 p-2">Shipping</td>
                        <td width="200" class="border border-gray-400 p-2"></td>
                    </tr>
                    <tr class="bg-green-200">
                        <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                        <td width="200" class="border border-gray-400 p-2">Total</td>
                        <td width="200" class="border border-gray-400 p-2"></td>
                    </tr>
                </table>
            </div>
            <div class="h-16 flex flex-shrink-0 border-t border-gray-200">
                <div @click="payOrder()" class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-green-500 text-white hover:bg-green-600 border-r border-green-600 flex-auto">
                    <i class="mr-2 text-3xl las la-cash-register"></i> 
                    <span class="text-2xl">Pay</span>
                </div>
                <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-blue-500 text-white border-r hover:bg-blue-600 border-blue-600 flex-auto">
                    <i class="mr-2 text-3xl las la-pause"></i> 
                    <span class="text-2xl">Hold</span>
                </div>
                <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-white border-r border-gray-200 hover:bg-indigo-100 flex-auto text-gray-700">
                    <i class="mr-2 text-3xl las la-percent"></i> 
                    <span class="text-2xl">Discount</span>
                </div>
                <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-red-500 text-white border-gray-200 hover:bg-red-600 flex-auto">
                    <i class="mr-2 text-3xl las la-trash"></i> 
                    <span class="text-2xl">Void</span>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { Popup } from '../../../libraries/popup';
import PosPayment from './ns-pos-payment';

export default {
    name: 'ns-pos-cart',
    data: () => {
        return {
            popup : null,
        }
    },
    mounted() {

    },
    methods: {
        payOrder() {
            this.popup  =   new Popup({
                primarySelector: '#pos-app',
                popupClass : `shadow-lg h-4/5-screen w-4/5 bg-white`
            });
            this.popup.open( PosPayment );
        }
    }
}
</script>