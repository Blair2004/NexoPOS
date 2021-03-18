<template>
    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="head bg-white flex-auto">
            <div class="head text-center border-b border-gray-200 text-gray-700 w-full py-2">
                <h5>Best Cashiers</h5>
            </div>
            <div class="body">
                <table class="table w-full" v-if="cashiers.length > 0">
                    <thead>
                        <tr v-for="cashier of cashiers" :key="cashier.id" class="border-gray-300 border-b text-sm">
                            <th class="p-2">
                                <div class="-mx-1 flex justify-start items-center">
                                    <div class="px-1">
                                        <div class="rounded-full bg-gray-200 h-6 w-6 ">
                                            <img src="/images/user.png"/>
                                        </div>
                                    </div>
                                    <div class="px-1 justify-center">
                                        <h3 class="font-semibold text-gray-600 items-center">{{ cashier.username }}</h3>
                                    </div>
                                </div>
                            </th>
                            <th class="flex justify-end text-green-700 p-2">{{ cashier.total_sales | currency( 'abbreviate' ) }}</th>
                        </tr>
                        <tr v-if="cashiers.length === 0">
                            <th colspan="2">No result to display.</th>
                        </tr>
                    </thead>
                </table>
                <div class="h-56 flex items-center justify-center" v-if="! hasLoaded">
                    <ns-spinner size="8" border="4"></ns-spinner>
                </div>
                <div class="h-56 flex items-center justify-center flex-col" v-if="hasLoaded && cashiers.length === 0">
                    <i class="las la-grin-beam-sweat text-6xl text-gray-700"></i>
                    <p class="text-gray-600 text-sm">Well.. nothing to show for the meantime.</p>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
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
    destroyed() {
        this.subscription.unsubscribe();
    }
}
</script>