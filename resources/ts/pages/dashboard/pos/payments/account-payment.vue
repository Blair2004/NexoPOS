<template>
    <div class="h-full w-full py-2">
        <div class="px-2 pb-2" v-if="order">
            <div class="grid grid-cols-2 gap-2">
                <div id="details" class="h-16 flex justify-between items-center bg-blue-400 text-white text-xl md:text-3xl p-2">
                    <span>Total : </span>
                    <span>{{ order.total | currency }}</span>
                </div>
                <div id="discount" @click="toggleDiscount()" class="cursor-pointer h-16 flex justify-between items-center bg-red-400 text-white text-xl md:text-3xl p-2">
                    <span>Discount : </span>
                    <span>{{ order.discount | currency }}</span>
                </div>
                <div id="paid" class="h-16 flex justify-between items-center bg-green-400 text-white text-xl md:text-3xl p-2">
                    <span>Paid : </span>
                    <span>{{ order.tendered | currency }}</span>
                </div>
                <div id="change" class="h-16 flex justify-between items-center bg-teal-400 text-white text-xl md:text-3xl p-2">
                    <span>Change : </span>
                    <span>{{ order.change | currency }}</span>
                </div>
                <div id="change" class="col-span-2 h-16 flex justify-between items-center bg-blue-400 text-white text-xl md:text-3xl p-2">
                    <span>Current Balance : </span>
                    <span>{{ order.customer.account_amount | currency }}</span>
                </div>
                <div id="change" class="col-span-2 h-16 flex justify-between items-center bg-gray-300 text-gray-800 text-xl md:text-3xl p-2">
                    <span>Screen : </span>
                    <span>{{ screenValue | currency }}</span>
                </div>
            </div>
        </div>
        <div class="px-2 pb-2">
            <div class="-mx-2 flex flex-wrap">
                <div class="pl-2 pr-1 flex-auto">
                    <ns-numpad @changed="handleChange( $event )" @next="proceedAddingPayment( $event )"></ns-numpad>
                </div>
                <div class="w-1/2 md:w-72 pr-2 pl-1">
                    <div class="grid grid-flow-row grid-rows-1 gap-2">
                        <div 
                            @click="increaseBy({ value : 100 })"
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span>{{ 100 | currency }}</span>
                        </div>
                        <div 
                            @click="increaseBy({ value : 500 })"
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span >{{ 500 | currency }}</span>
                        </div>
                        <div 
                            @click="increaseBy({ value : 1000 })"
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span >{{ 1000 | currency }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { default as nsNumpad } from "@/components/ns-numpad";

export default {
    name: "ns-account-payment",
    components: {
        nsNumpad
    },
    data() {
        return {
            subscription: null,
            screenValue: 0,
            order: null,
        }
    },
    methods: {
        handleChange( event ) {
            this.screenValue    =   event;
        },
        proceedAddingPayment( event ) {
            console.log( event );
        },
    },
    mounted() {
        this.subscription   =   POS.order.subscribe( order => this.order = order );
    },
    destroyed() {
        this.subscription.unsubscribe();
    }
}
</script>