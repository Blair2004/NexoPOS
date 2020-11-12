<template>
    <div class="">
        <div class="flex -mx-4 flex-wrap">
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-16 p-2 flex justify-between items-center bg-blue-400 text-white text-2xl font-bold">
                    <span>Total</span>
                    <span>{{ order.total | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-16 p-2 flex justify-between items-center  bg-green-400 text-white text-2xl font-bold">
                    <span>Paid</span>
                    <span>{{ order.tendered | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-16 p-2 flex justify-between items-center  bg-red-400 text-white text-2xl font-bold">
                    <span>Unpaid</span>
                    <span>{{ order.total - order.tendered | currency }}</span>
                </div>
            </div>
            <div class="px-2 w-full md:w-1/2">
                <div class="my-1 h-16 p-2 flex justify-between items-center  bg-teal-400 text-white text-2xl font-bold">
                    <span>Customer Account</span>
                    <span>{{ order.customer.account_amount | currency }}</span>
                </div>
            </div>
        </div>
        <div class="flex -mx-4 flex-wrap">
            <div class="px-2 w-full mb-4 md:w-1/2">
                <h3 class="font-semibold border-b-2 border-blue-400 py-2">
                    Payment
                </h3>
                <div class="py-2">
                    <ns-field v-for="(field, index) of fields" :field="field" :key="index"></ns-field>
                    <div class="my-2 h-16 p-2 flex justify-end items-center bg-gray-200">
                        {{ inputValue | currency }}
                    </div>
                    <ns-numpad @changed="updateValue( $event )" :value="inputValue"></ns-numpad>
                </div>
            </div>
            <div class="px-2 w-full mb-4 md:w-1/2">
                <h3 class="font-semibold border-b-2 border-blue-400 py-2 mb-2">
                    Payment History
                </h3>
                <ul>
                    <li v-for="payment of order.payments" :key="payment.id" class="p-2 flex items-center justify-between text-shite bg-gray-300 mb-2">
                        <span>{{ payment.identifier }}</span>
                        <span>{{ payment.value | currency }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script>
import nsNumpad from "@/components/ns-numpad.vue";
export default {
    props: [ 'order' ],
    data() {
        return {
            inputValue: 0,
            fields: [
                {
                    type: 'select',
                    label: 'Payment Type',
                    name: 'payment_type',
                }
            ]
        }
    },
    methods: {
        updateValue( value ) {
            this.inputValue     =   value;
        }
    },
    components: {
        nsNumpad
    }
}
</script>