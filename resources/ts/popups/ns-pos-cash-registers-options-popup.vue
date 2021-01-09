<script>
import popupCloser from '@/libraries/popup-closer'
import nsPosCashRegistersActionPopupVue from './ns-pos-cash-registers-action-popup.vue';
export default {
    mounted() {
        this.popupCloser();
    },
    methods: {
        popupCloser,

        async closeCashRegister() {
            try {
                const response  =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosCashRegistersActionPopupVue, {
                        title: 'Close Register',
                        action: 'close',
                        identifier: 'ns.cash-registers-closing',
                        resolve, 
                        reject
                    })
                });
            } catch( exception ) {
                
            }
        }
    }
}
</script>
<template>
    <div class="shadow-lg w-95vw md:w-3/5-screen lg:w-1/3-screen bg-white">
        <div class="p-2 border-b border-gray-200">
            <h3>Register Options</h3>
        </div>
        <div class="grid grid-cols-2 text-gray-700">
            <div @click="closeCashRegister()" class="hover:bg-teal-100 border-gray-200 border-r border-b py-4 cursor-pointer px-2 flex items-center justify-center flex-col">
                <i class="las la-sign-out-alt text-6xl"></i>
                <h3 class="text-xl font-bold">Close</h3>
            </div>
            <div class=" hover:bg-green-100 border-gray-200 border-r border-b py-4 cursor-pointer px-2 flex items-center justify-center flex-col">
                <i class="las la-plus-circle text-6xl"></i>
                <h3 class="text-xl font-bold">Cash In</h3>
            </div>
            <div class="hover:bg-red-100 border-gray-200 border-r border-b py-4 cursor-pointer px-2 flex items-center justify-center flex-col">
                <i class="las la-minus-circle text-6xl"></i>
                <h3 class="text-xl font-bold">Cash Out</h3>
            </div>
            <div class="hover:bg-blue-100 border-gray-200 border-r border-b py-4 cursor-pointer px-2 flex items-center justify-center flex-col">
                <i class="las la-history text-6xl"></i>
                <h3 class="text-xl font-bold">History</h3>
            </div>
        </div>
    </div>
</template>