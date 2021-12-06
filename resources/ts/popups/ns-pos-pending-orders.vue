<template>
    <div class="flex flex-auto flex-col overflow-hidden">
        <div class="p-1">
            <div class="flex rounded border-2 border-blue-400">
                <input @keyup.enter="searchOrder()" v-model="searchField" type="text" class="p-2 outline-none flex-auto">
                <button @click="searchOrder()" class="w-16 md:w-24 bg-blue-400 text-white">
                    <i class="las la-search"></i>
                    <span class="mr-1 hidden md:visible">{{ __( 'Search' ) }}</span>
                </button>
            </div>
        </div>
        <div class="overflow-y-auto flex flex-auto">
            <div class="flex p-2 flex-auto flex-col overflow-y-auto">
                <div :data-order-id="order.id" class="border-b border-blue-400 w-full py-2" v-for="order of orders" :key="order.id">
                    <h3 class="text-gray-700">{{ order.title || 'Untitled Order' }}</h3>
                    <div class="px-2">
                        <div class="flex flex-wrap -mx-4">
                            <div class="w-full md:w-1/2 px-2">
                                <p class="text-sm text-gray-600"><strong>{{ __( 'Code' ) }}</strong> : {{ order.code }}</p>
                                <p class="text-sm text-gray-600"><strong>{{ __( 'Cashier' ) }}</strong> : {{ order.nexopos_users_username }}</p>
                                <p class="text-sm text-gray-600"><strong>{{ __( 'Total' ) }}</strong> : {{ order.total | currency }}</p>
                                <p class="text-sm text-gray-600"><strong>{{ __( 'Tendered' ) }}</strong> : {{ order.tendered | currency }}</p>
                            </div>
                            <div class="w-full md:w-1/2 px-2">
                                <p class="text-sm text-gray-600"><strong>{{ __( 'Customer' ) }}</strong> : {{ order.nexopos_customers_name }}</p>
                                <p class="text-sm text-gray-600"><strong>{{ __( 'Date' ) }}</strong> : {{ order.created_at }}</p>
                                <p class="text-sm text-gray-600"><strong>{{ __( 'Type' ) }}</strong> : {{ order.type }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end w-full mt-2">
                        <div class="flex rounded-lg overflow-hidden buttons-container">
                            <button @click="proceedOpenOrder( order )" class="text-white bg-green-400 outline-none px-2 py-1"><i class="las la-lock-open"></i> {{ __( 'Open' ) }}</button>
                            <button @click="previewOrder( order )" class="text-white bg-blue-400 outline-none px-2 py-1"><i class="las la-eye"></i> {{ __( 'Products' ) }}</button>
                            <button @click="printOrder( order )" class="text-white bg-teal-400 outline-none px-2 py-1"><i class="las la-print"></i> {{ __( 'Print' ) }}</button>
                        </div>
                    </div>
                </div>
                <div v-if="orders.length === 0" class="h-full v-full items-center justify-center flex">
                    <h3 class="text-semibold text-gray-700">{{ __( 'Nothing to display...' ) }}</h3>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHooks } from '@/bootstrap';
import { __ } from '@/libraries/lang';
export default {
    props: [ 'orders' ],
    data() {
        return {
            searchField: '',
        }
    },
    watch: {
        orders() {
            nsHooks.doAction( 'ns-pos-pending-orders-refreshed', this.orders );
        }
    },
    mounted() {

    },
    name: "ns-pos-pending-order",
    methods: {
        __,
        
        previewOrder( order ) {
            this.$emit( 'previewOrder', order );
        },
        proceedOpenOrder( order ) {
            this.$emit( 'proceedOpenOrder', order );
        },
        searchOrder() {
            this.$emit( 'searchOrder', this.searchField );
        },
        printOrder( order ) {
            this.$emit( 'printOrder', order );
        }
    }
}
</script>