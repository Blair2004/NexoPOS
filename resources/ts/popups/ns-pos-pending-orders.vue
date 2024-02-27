<template>
    <div class="flex flex-auto flex-col overflow-hidden">
        <div class="p-1">
            <div class="flex rounded border-2 input-group info">
                <input @keyup.enter="searchOrder()" v-model="searchField" type="text" class="p-2 outline-none flex-auto">
                <button @click="searchOrder()" class="w-16 md:w-24">
                    <i class="las la-search"></i>
                    <span class="mr-1 hidden md:visible">{{ __( 'Search' ) }}</span>
                </button>
            </div>
        </div>
        <div class="overflow-y-auto flex flex-auto">
            <div class="flex p-2 flex-auto flex-col overflow-y-auto">
                <div :data-order-id="order.id" class="border-b ns-box-body w-full py-2 ns-order-line" v-for="order of orders" :key="order.id">
                    <h3 class="text-primary">{{ order.title || 'Untitled Order' }}</h3>
                    <div class="px-2">
                        <div class="flex flex-wrap -mx-4">
                            <div class="w-full md:w-1/2 px-2">
                                <p v-for="(line,key) of columns.leftColumn" :key="key" class="text-sm text-primary"><strong>{{ line.label }}</strong> : {{ line.value( order ) }}</p>
                            </div>
                            <div class="w-full md:w-1/2 px-2">
                                <p v-for="(line,key) of columns.rightColumn" :key="key" class="text-sm text-primary"><strong>{{ line.label }}</strong> : {{ line.value( order ) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end w-full mt-2">
                        <div class="flex rounded-lg overflow-hidden ns-buttons">
                            <button @click="proceedOpenOrder( order )" class="info outline-none px-2 py-1"><i class="las la-lock-open"></i> {{ __( 'Open' ) }}</button>
                            <button @click="previewOrder( order )" class="success outline-none px-2 py-1"><i class="las la-eye"></i> {{ __( 'Products' ) }}</button>
                            <button @click="printOrder( order )" class="warning outline-none px-2 py-1"><i class="las la-print"></i> {{ __( 'Print' ) }}</button>
                        </div>
                    </div>
                </div>
                <div v-if="orders.length === 0" class="h-full v-full items-center justify-center flex">
                    <h3 class="text-semibold text-primary">{{ __( 'Nothing to display...' ) }}</h3>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { nsHooks } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';

export default {
    props: [ 'orders' ],
    data() {
        return {
            searchField: '',
            columns: {
                rightColumn: [],
                leftColumn: []
            }
        }
    },
    watch: {
        orders() {
            this.$nextTick(() => {
                nsHooks.doAction( 'ns-pos-pending-orders-refreshed', this.orders.map( order => {
                    return {
                    order,
                    dom: document.querySelector( `[data-order-id="${order.id}"]` )
                    }
                }));
            });
        }
    },
    
    mounted() {

        this.columns.leftColumn    =   nsHooks.applyFilters( 'ns-pending-orders-left-column', [
            {
                label: __( 'Code' ),
                value: ( order ) => order.code
            }, {
                label: __( 'Cashier' ),
                value: ( order ) => order.user_username
            }, {
                label: __( 'Total' ),
                value: ( order ) => order.total
            }, {
                label: __( 'Tendered' ),
                value: ( order ) => order.tendered
            },
        ]);

        this.columns.rightColumn    =   nsHooks.applyFilters( 'ns-pending-orders-right-column', [
            {
                label: __( 'Customer' ),
                value: ( order ) => `${order.customer_first_name} ${order.customer_last_name}`
            }, {
                label: __( 'Date' ),
                value: ( order ) => order.created_at
            }, {
                label: __( 'Type' ),
                value: ( order ) => order.type
            }, 
        ]);

        this.popupCloser();
    },
    name: "ns-pos-pending-order",
    methods: {
        __,
        popupCloser,
        
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