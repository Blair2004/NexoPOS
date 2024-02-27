<template>
    <div class="shadow-lg w-95vw h-95vh md:w-3/5-screen md:h-3/5-screen ns-box flex flex-col overflow-hidden">
        <div class="border-b p-2 flex items-center justify-between ns-box-header">
            <h3>{{ __( 'Order Refunds' ) }}</h3>
            <div class="flex">
                <div v-if="view === 'details'" @click="view = 'summary'" class="flex items-center justify-center cursor-pointer rounded-full px-3 border ns-inset-button mr-1">{{ __( 'Go Back' ) }}</div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>            
        <div class="overflow-auto flex-auto ns-box-body">
            <template v-if="view === 'summary'">
                <div class="flex h-full w-full items-center justify-center" v-if="! loaded">
                    <ns-spinner size="24"></ns-spinner>
                </div>
                <div class="flex h-full w-full items-center flex-col justify-center" v-if="loaded && refunds.length === 0">
                    <i class="las la-laugh-wink text-5xl"></i>
                    <p class="md:w-80 text-sm text-secondary text-center">{{ __( 'No refunds made so far. Good news right?' ) }}</p>
                </div>
                <template v-if="loaded && refunds.length > 0">
                    <div class="border-b border-box-edge flex flex-col md:flex-row" :key="refund.id" v-for="refund of refunds">
                        <div class="w-full md:flex-auto p-2">
                            <h3 class="font-semibold mb-1">{{ order.code }}</h3>
                            <div>
                                <ul class="flex -mx-1 text-sm text-primary">
                                    <li class="px-1">{{ __( 'Total' ) }} : {{ nsCurrency( refund.total ) }}</li>
                                    <li class="px-1">{{ __( 'By' ) }} : {{ refund.author.username }}</li>
                                </ul>
                            </div>
                        </div>
                        <div @click="toggleProductView( refund )" class="w-full md:w-16 cursor-pointer hover:bg-info-secondary hover:border-info-primary hover:text-white text-lg flex items-center justify-center border-box-edge md:border-l">
                            <i class="las la-eye"></i>
                        </div>
                        <div @click="printRefundReceipt( refund )" class="w-full md:w-16 cursor-pointer hover:bg-info-secondary hover:border-info-primary hover:text-white text-lg flex items-center justify-center border-box-edge md:border-l">
                            <i class="las la-print"></i>
                        </div>
                    </div>
                </template>
            </template>
            <template v-if="view === 'details'">
                <div class="border-b border-box-edge flex flex-col md:flex-row" :key="product.id" v-for="product of previewed.refunded_products">
                    <div class="w-full md:flex-auto p-2">
                        <h3 class="font-semibold mb-1">{{ product.product.name }}</h3>
                        <div>
                            <ul class="flex -mx-1 text-sm text-primary">
                                <li class="px-1">{{ __( 'Condition' ) }} : {{ product.condition }}</li>
                                <li class="px-1">{{ __( 'Quantity' ) }} : {{ product.quantity }}</li>
                                <li class="px-1">{{ __( 'Total' ) }} : {{ nsCurrency( product.total_price ) }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
<script>
import { __ } from "~/libraries/lang";
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';
import { nsSnackBar } from '~/bootstrap';
import Print from '~/libraries/print';
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'ns-orders-refund-popup',
    props: [ 'popup' ],
    data() {
        return {
            order: null,
            refunds: [],
            view: 'summary',
            previewed: null,
            loaded: false,
            options: systemOptions,
            systemUrls,
            print: new Print({ urls: systemUrls, options: systemOptions })
        }
    },
    methods: {
        __,
        nsCurrency,
        popupCloser,
        popupResolver,

        toggleProductView( refund ) {
            this.view       =   'details';
            this.previewed  =   refund;
        },

        loadOrderRefunds() {
            nsHttpClient.get( `/api/orders/${this.order.id}/refunds` )
                .subscribe( order => {
                    this.loaded     =   true;
                    this.refunds    =   order.refunds;
                }, ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                })
        },

        close() {
            this.popup.close();
        },

        printRefundReceipt( refund ) {
            this.print.process( refund.id, 'refund' )
        }
    },
    mounted() {
        this.order      =   this.popup.params.order;
        this.popupCloser();
        this.loadOrderRefunds();
    }
}
</script>