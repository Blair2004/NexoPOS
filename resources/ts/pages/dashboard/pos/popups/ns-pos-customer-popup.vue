<template>
    <div class="bg-white shadow-xl w-4/5-screen md:w-2/5-screen xl:w-1/5-screen">
        <div id="header" class="border-b border-gray-200 text-center font-semibold text-2xl text-gray-700 py-2">
            <h2>Select Customer</h2>
        </div>
        <div class="p-2 border-b border-gray-200 flex justify-between text-gray-600">
            <span>Selected : </span>
            <span>{{ order.customer ? order.customer.name : 'N/A' }}</span>
        </div>
        <div class="p-2 border-b border-gray-200 flex justify-between text-gray-600">
            <input 
                v-model="searchCustomerValue"
                placeholder="Search Customer" 
                type="text" 
                class="rounded border-2 border-blue-400 bg-gray-100 w-full p-2">
        </div>
        <div class="h-56 overflow-y-auto">
            <ul>
                <li class="p-2 text-center text-gray-600" v-if="customers && customers.length === 0">
                    No customer match your query...
                </li>
                <li @click="selectCustomer( customer )" v-for="customer of customers" :key="customer.id" class="cursor-pointer hover:bg-gray-100 p-2 border-b border-gray-200 text-gray-600 flex justify-between">
                    <span>{{ customer.name }}</span>
                    <p>
                        <span v-if="owe_amount > 0" class="text-red-600">-{{ owe_amount | currency }}</span>
                        <span v-if="owe_amount > 0">/</span>
                        <span class="text-green-600">{{ purchases_amount | currency }}</span>
                    </p>
                </li>
            </ul>
        </div>
        <div class="p-2 border-b border-gray-200 flex justify-between text-gray-600">
            <button :class="customerSelected ? '' : 'bg-gray-400 text-gray-700'" :disabled="! customerSelected" class="rounded p-2">Select</button>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '../../../../bootstrap';
export default {
    data() {
        return {
            searchCustomerValue: '',
            orderSubscription: null,
            order: {},
            debounceSearch: null,
            customers: []
        }
    },
    computed: {
        customerSelected() {
            return false;
        }
    },
    watch: {
        searchCustomerValue( value ) {
            clearTimeout( this.debounceSearch );
            this.debounceSearch     =   setTimeout( () => {
                this.searchCustomer( value );
            }, 500 );
        }
    },
    mounted() {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                this.$popup.close();
            }
        });

        this.orderSubscription  =   POS.order.subscribe( order => {
            this.order      =   order;
        });
    },
    destroyed() {
        this.orderSubscription.unsubscribe();
    },
    methods: {
        selectCustomer( customer ) {
            this.customers.forEach( customer => customer.selected = false );
            customer.selected   =   true;

            /**
             * define the customer using the default
             * POS object;
             */
            POS.definedCustomer( customer );
            this.$popup.close();
        },
        searchCustomer( value ) {
            nsHttpClient.post( '/api/nexopos/v4/customers/search', {
                sarch: value
            }).subscribe( customers => {
                customers.forEach( customer => customer.selected = false );
                this.customers  =   customers;
            })
        }
    }
}
</script>