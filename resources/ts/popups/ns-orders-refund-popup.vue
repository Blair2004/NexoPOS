<template>
    <div class="shadow-lg w-95vw h-95vh md:w-3/5-screen md:h-3/5-screen ns-box flex flex-col overflow-hidden">
        <div class="border-b p-2 flex items-center justify-between ns-box-header">
            <h3>{{ __( 'Order Refunds' ) }}</h3>
            <div class="flex">
                <div v-if="view === 'details'" @click="view = 'summary'" class="flex items-center justify-center cursor-pointer rounded-full px-3 border hover:bg-info-primary hover:text-white mr-1">{{ __( 'Go Back' ) }}</div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>            
        <div class="overflow-auto flex-auto ns-box-body">
            <template v-if="view === 'summary'">
                <div class="flex h-full w-full items-center justify-center" v-if="! loaded">
                    <ns-spinner size="24"></ns-spinner>
                </div>
                <div class="flex h-full w-full items-center justify-center" v-if="loaded && refunds.length === 0">
                    <i class="lar la-frown-open"></i>
                </div>
                <template v-if="loaded && refunds.length > 0">
                    <div class="border-b flex flex-col md:flex-row" :key="refund.id" v-for="refund of refunds">
                        <div class="w-full md:flex-auto p-2">
                            <h3 class="font-semibold mb-1">{{ order.code }}</h3>
                            <div>
                                <ul class="flex -mx-1 text-sm text-primary">
                                    <li class="px-1">{{ __( 'Total' ) }} : {{ refund.total | currency }}</li>
                                    <li class="px-1">{{ __( 'By' ) }} : {{ refund.author.username }}</li>
                                </ul>
                            </div>
                        </div>
                        <div @click="toggleProductView( refund )" class="w-full md:w-16 cursor-pointer hover:bg-info-primary hover:border-info-primary hover:text-white text-lg flex items-center justify-center md:border-l">
                            <i class="las la-eye"></i>
                        </div>
                        <div @click="printRefundReceipt( refund )" class="w-full md:w-16 cursor-pointer hover:bg-info-primary hover:border-info-primary hover:text-white text-lg flex items-center justify-center md:border-l">
                            <i class="las la-print"></i>
                        </div>
                    </div>
                </template>
            </template>
            <template v-if="view === 'details'">
                <div class="border-b flex flex-col md:flex-row" :key="product.id" v-for="product of previewed.refunded_products">
                    <div class="w-full md:flex-auto p-2">
                        <h3 class="font-semibold mb-1">{{ product.product.name }}</h3>
                        <div>
                            <ul class="flex -mx-1 text-sm text-primary">
                                <li class="px-1">{{ __( 'Condition' ) }} : {{ product.condition }}</li>
                                <li class="px-1">{{ __( 'Quantity' ) }} : {{ product.quantity }}</li>
                                <li class="px-1">{{ __( 'Total' ) }} : {{ product.total_price | currency }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
<script>
import { __ } from "@/libraries/lang";
import popupCloser from '@/libraries/popup-closer';
import popupResolver from '@/libraries/popup-resolver';
import { nsSnackBar } from '@/bootstrap';
import Print from '@/libraries/print';

export default {
    name: 'ns-orders-refund-popup',
    data() {
        return {
            order: null,
            refunds: [],
            view: 'summary',
            previewed: null,
            loaded: false,
            options: systemOptions,
            settings: systemSettings,
            print: new Print({ settings: systemSettings, options: systemOptions, type: 'refund' })
        }
    },
    methods: {
        __,
        popupCloser,
        popupResolver,

        toggleProductView( refund ) {
            this.view       =   'details';
            this.previewed  =   refund;
        },

        loadOrderRefunds() {
            nsHttpClient.get( `/api/nexopos/v4/orders/${this.order.id}/refunds` )
                .subscribe( order => {
                    this.loaded     =   true;
                    this.refunds    =   order.refunds;
                }, ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                })
        },

        close() {
            this.$popup.close();
        },

        printRefundReceipt( refund ) {
            this.print.printOrder( refund.id )
        }
    },
    mounted() {
        this.order      =   this.$popupParams.order;
        this.popupCloser();
        this.loadOrderRefunds();
    }
}
</script>