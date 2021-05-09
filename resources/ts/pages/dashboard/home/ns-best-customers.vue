<template>
    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="head bg-white flex-auto">
            <div class="head text-center border-b border-gray-200 text-gray-700 w-full py-2">
                <h5>{{ __( 'Best Customers' ) }}</h5>
            </div>
            <div class="body">
                <div v-if="! hasLoaded" class="h-56 w-full flex items-center justify-center">
                    <ns-spinner size="12" border="4"></ns-spinner>
                </div>
                <div class="h-56 flex items-center justify-center flex-col" v-if="hasLoaded && customers.length === 0">
                    <i class="las la-grin-beam-sweat text-6xl text-gray-700"></i>
                    <p class="text-gray-600 text-sm">{{ __( 'Well.. nothing to show for the meantime' ) }}</p>
                </div>
                <table class="table w-full" v-if="customers.length > 0">
                    <thead>
                        <tr v-for="customer of customers" :key="customer.id" class="border-gray-300 border-b text-sm">
                            <th class="p-2">
                                <div class="-mx-1 flex justify-start items-center">
                                    <div class="px-1">
                                        <div class="rounded-full bg-gray-200 h-6 w-6 ">
                                            <img src="/images/user.png"/>
                                        </div>
                                    </div>
                                    <div class="px-1 justify-center">
                                        <h3 class="font-semibold text-gray-600 items-center">{{ customer.name }}</h3>
                                    </div>
                                </div>
                            </th>
                            <th class="flex justify-end text-green-700 p-2">{{ customer.purchases_amount | currency }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '@/bootstrap';
import { __ } from '@/libraries/lang';
export default {
    name: 'ns-best-customers' ,
    mounted() {
        this.hasLoaded      =   false;
        this.subscription   =   Dashboard.bestCustomers.subscribe( customers => {
            this.hasLoaded  =   true;
            this.customers  =   customers;
        });
    },
    methods: {
        __
    },
    data() {
        return {
            customers: [],
            subscription: null,
            hasLoaded: false,
        }
    },
    destroyed() {
        this.subscription.unsubscribe();
    }
}
</script>