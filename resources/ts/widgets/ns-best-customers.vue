<template>
    <div id="ns-best-customers" class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="flex-auto">
            <div class="head text-center flex justify-between items-center border-b w-full p-2">
                <h5>{{ __( 'Best Customers' ) }}</h5>
                <div>
                    <ns-close-button @click="$emit( 'onRemove' )"></ns-close-button>
                </div>
            </div>
            <div class="body flex flex-col h-64" :class="customers.length === 0 ? 'body flex items-center justify-center flex-col h-64' : ''">
                <div v-if="! hasLoaded" class="w-full flex items-center justify-center">
                    <ns-spinner size="12" border="4"></ns-spinner>
                </div>
                <div class="flex items-center justify-center flex-col" v-if="hasLoaded && customers.length === 0">
                    <i class="las la-grin-beam-sweat text-6xl"></i>
                    <p class="text-sm">{{ __( 'Well.. nothing to show for the meantime' ) }}</p>
                </div>
                <table class="table w-full" v-if="customers.length > 0">
                    <thead>
                        <tr v-for="customer of customers" :key="customer.id" class="entry border-b text-sm">
                            <th class="p-2"> 
                                <div class="-mx-1 flex justify-start items-center">
                                    <div class="px-1">
                                        <div class="rounded-full">
                                            <i class="las la-user-circle text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="px-1 justify-center">
                                        <h3 class="font-semibold  items-center">{{ customer.first_name }} {{ customer.last_name }}</h3>
                                    </div>
                                </div>
                            </th>
                            <th class="flex justify-end amount p-2">{{ nsCurrency( customer.purchases_amount ) }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import { nsCurrency, nsRawCurrency } from '~/filters/currency';

export default {
    mounted() {
        this.hasLoaded      =   false;
        this.subscription   =   Dashboard.bestCustomers.subscribe( customers => {
            this.hasLoaded  =   true;
            this.customers  =   customers;
        });
    },
    methods: {
        __,
        nsCurrency,
        nsRawCurrency,
    },
    data() {
        return {
            customers: [],
            subscription: null,
            hasLoaded: false,
        }
    },
    unmounted() {
        this.subscription.unsubscribe();
    }
}
</script>