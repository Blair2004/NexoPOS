<template>
    <div class="shadow-lg ns-box w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="border-b ns-box-header p-2 flex justify-between items-center">
            <h3 class="font-bold">{{ __( 'Coupons' ) }}</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2">
            <div class="p-4" v-if="! hasLoaded">
                <ns-spinner></ns-spinner>
            </div>
            <ul v-if="hasLoaded">
                <li v-for="coupon of coupons" :key="coupon.id" class="p-2 cursor-pointer flex justify-between surface-elevation border">
                    <span>{{ coupon.name }}</span>
                    <span>
                        <button class="rounded-full px-3 py-1">{{ __( 'Use' )}}</button>
                    </span>
                </li>
                <li class="py-3 text-center" v-if="coupons.length === 0 && hasLoaded">{{ __( 'No coupon available for this customer' ) }}</li>
            </ul>
        </div>
    </div>
</template>
<script>
/**
 * @deprecated
 */
import popupResolver from '~/libraries/popup-resolver';
import popupCloser from '~/libraries/popup-closer';
import { nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';

export default {
    name: "ns-pos-coupons-popup",
    props: [ 'popup' ],
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
    unmounted() {
        this.orderSubscriber.unsubscribe();
    },
    methods: {
        __,
        
        popupCloser,

        popupResolver,

        closePopup() {
            this.popupResolver( false );
        },

        loadCoupons() {
            this.hasLoaded  =   false;
            nsHttpClient.get( `/api/customers/${this.order.customer_id}/coupons` )
                .subscribe( coupons => {
                    this.hasLoaded  =   true;
                    this.coupons    =   coupons;
                })
        }
    }
}
</script>