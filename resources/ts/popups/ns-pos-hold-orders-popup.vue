<template>
    <div class="bg-white shadow-lg w-6/7-screen md:w-3/7-screen lg:w-2/6-screen">
        <div class="p-2 flex justify-between border-b items-center">
            <h3 class="font-semibold">Hold Order</h3>
            <div>
                <ns-close-button @click="$popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto">
            <div class="border-b h-16 flex items-center justify-center">
                <span class="text-5xl text-gray-700">{{ order.total | currency }}</span>
            </div>
            <div class="p-2">
                <input @keyup.enter="submitHold()" v-model="title" ref="reference" type="text" placeholder="Order Reference" class="rounded border-2 border-blue-400 bg-white p-2 w-full">
            </div>
            <div class="p-2">
                <p class="text-gray-600">
                    The current order will be set on hold. You can retreive this order from the pending order button. 
                    Providing a reference to it might help you to identify the order more quickly.
                </p>
            </div>
        </div>
        <div class="flex">
            <div @click="submitHold()" class=" cursor-pointer w-1/2 py-3 flex justify-center items-center bg-green-500 text-white font-semibold">
                Confirm
            </div>
            <div @click="$popup.close()" class="cursor-pointer w-1/2 py-3 flex justify-center items-center bg-red-500 text-white font-semibold">
                Cancel
            </div>
        </div>
    </div>
</template>
<script>
import popupCloser from "@/libraries/popup-closer";
export default {
    name: 'ns-pos-hold-orders',
    data() {
        return {
            order: {},
            title: '',
        }
    },
    mounted() {
        this.popupCloser();
        this.$refs[ 'reference' ].focus();
        this.$refs[ 'reference' ].select();
        this.order  =   this.$popupParams.order;
        this.title  =   this.$popupParams.order.title || '';
    },
    methods: {
        popupCloser,

        submitHold() {
            this.$popupParams.resolve({ title: this.title });
            this.$popup.close();
        }
    }
}
</script>