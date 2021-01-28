<template>
    <div class="shadow-lg bg-white w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="border-b border-gray-200 p-2 flex justify-between items-center">
            <h3 class="font-bold">Coupons</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2">
            <div class="p-4" v-if="! hasLoaded">
                <ns-spinner></ns-spinner>
            </div>
            <ul v-if="hasLoaded">
                <li v-for="coupon of coupons" :key="coupon.id" class="p-2 cursor-pointer flex justify-between bg-gray-100 hover:bg-blue-200">
                    <span>{{ coupon.name }}</span>
                    <span>
                        <button class="rounded-full px-3 py-1">Use</button>
                    </span>
                </li>
                <li class="py-3 text-center" v-if="coupons.length === 0 && hasLoaded">No coupon available for this customer</li>
            </ul>
        </div>
    </div>
</template>
<script>
import popupResolver from '@/libraries/popup-resolver';
import popupCloser from '@/libraries/popup-closer';
import { nsSnackBar } from '@/bootstrap';

export default {
    name: "ns-pos-coupons-popup",
    data() {
        return {
            orderSubscriber: null,
            hasLoaded: false,
            order: null,
            coupons: []
        }
    },
    mounted() {
        this.popupCloser();

        this.orderSubscriber    =   POS.order.subscribe( order => {
            this.order  =   order;
        });

        this.loadCoupons();
    },
    destroyed() {
        this.orderSubscriber.unsubscribe();
    },
    methods: {
        popupCloser,

        popupResolver,

        closePopup() {
            this.popupResolver( false );
        },

        loadCoupons() {
            this.hasLoaded  =   false;
            nsHttpClient.get( `/api/nexopos/v4/customers/${this.order.customer_id}/coupons` )
                .subscribe( coupons => {
                    this.hasLoaded  =   true;
                    this.coupons    =   coupons;
                })
        }
    }
}
</script>