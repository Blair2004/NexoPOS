<template>
    <div id="ns-best-cashiers" class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="flex-auto">
            <div class="head text-center border-b w-full flex justify-between items-center p-2">
                <h5>{{ __( 'Best Cashiers' ) }}</h5>
                <div>
                    <ns-close-button @click="$emit( 'onRemove' )"></ns-close-button>
                </div>
            </div>
            <div class="body">
                <table class="table w-full" v-if="cashiers.length > 0">
                    <thead>
                        <tr v-for="cashier of cashiers" :key="cashier.id" class="entry border-b text-sm">
                            <th class="p-2">
                                <div class="-mx-1 flex justify-start items-center">
                                    <div class="px-1">
                                        <div class="rounded-full">
                                            <i class="las la-user-circle text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="px-1 justify-center">
                                        <h3 class="font-semibold items-center">{{ cashier.username }}</h3>
                                    </div>
                                </div>
                            </th>
                            <th class="flex justify-end p-2">{{ nsCurrency( cashier.total_sales, 'abbreviate' ) }}</th>
                        </tr>
                        <tr v-if="cashiers.length === 0">
                            <th colspan="2">{{ __( 'No result to display.' ) }}</th>
                        </tr>
                    </thead>
                </table>
                <div class="h-56 flex items-center justify-center" v-if="! hasLoaded">
                    <ns-spinner size="8" border="4"></ns-spinner>
                </div>
                <div class="h-56 flex items-center justify-center flex-col" v-if="hasLoaded && cashiers.length === 0">
                    <i class="las la-grin-beam-sweat text-6xl"></i>
                    <p class="text-sm text-center">{{ __( 'Well.. nothing to show for the meantime.' ) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-best-customers',
    data() {
        return {
            subscription: null,
            cashiers: [],
            hasLoaded: false,
        }
    },
    mounted() {
        this.hasLoaded      =   false;
        this.subscription    =   Dashboard.bestCashiers.subscribe( cashiers => {
            this.hasLoaded  =   true;
            this.cashiers   =   cashiers;
        });
    },
    methods: {
        __,
        nsCurrency,
    },
    unmounted() {
        this.subscription.unsubscribe();
    }
}
</script>