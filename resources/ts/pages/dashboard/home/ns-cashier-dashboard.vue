<template>
    <div>
        <div class="flex -mx-4 flex-wrap">
            <div class="px-4 w-full mb-6" :class="showCommission ? 'md:w-1/2 lg:w-1/4' : 'md:w-1/3'">
                <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-purple-400 to-purple-600 text-white px-3 py-5">
                    <div class="flex flex-row md:flex-col flex-auto">
                        <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Total Sales' ) }}</h6>
                            <h3 class="text-2xl font-black">
                                {{ nsCurrency( report.total_sales_amount, 'abbreviate' ) }}
                            </h3>
                        </div>
                        <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                            <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Total Sales' ) }}</h6>
                            <h4 class="text-xs text-right">+{{ nsCurrency( report.today_sales_amount ) }} {{ __( 'Today' ) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-4 w-full mb-6" :class="showCommission ? 'md:w-1/2 lg:w-1/4' : 'md:w-1/3'">
                <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-red-400 to-red-600 text-white px-3 py-5">
                    <div class="flex flex-row md:flex-col flex-auto">
                        <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Total Refunds' ) }}</h6>
                            <h3 class="text-2xl font-black">
                                {{ nsCurrency( report.total_refunds_amount, 'abbreviate' ) }}
                            </h3>
                        </div>
                        <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                            <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Total Refunds' ) }}</h6>
                            <h4 class="text-xs text-right">+{{ nsCurrency( report.today_refunds_amount ) }} {{ __( 'Today' ) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-4 w-full mb-6" :class="showCommission ? 'md:w-1/2 lg:w-1/4' : 'md:w-1/3'">
                <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-blue-400 to-blue-600 text-white px-3 py-5">
                    <div class="flex flex-row md:flex-col flex-auto">
                        <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Clients Registered' ) }}</h6>
                            <h3 class="text-2xl font-black">
                                {{ ( report.total_customers ) }}
                            </h3>
                        </div>
                        <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                            <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Clients Registered' ) }}</h6>
                            <h4 class="text-xs text-right">+{{ ( report.today_customers ) }} {{ __( 'Today' ) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="showCommission" class="px-4 w-full mb-6" :class="showCommission ? 'md:w-1/2 lg:w-1/4' : 'md:w-1/3'">
                <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-green-400 to-green-600 text-white px-3 py-5">
                    <div class="flex flex-row md:flex-col flex-auto">
                        <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Commissions' ) }}</h6>
                            <h3 class="text-2xl font-black">
                                {{ nsCurrency( report.total_commissions ) }}
                            </h3>
                        </div>
                        <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                            <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Commissions' ) }}</h6>
                            <h4 class="text-xs text-right">+{{ nsCurrency( report.today_commissions ) }} {{ __( 'Today' ) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="py-4">
            <ul v-if="report.today_orders && report.today_orders.length > 0" class="bg-white shadow-lg rounded overflow-hidden">
                <li v-for="order of report.today_orders" :key="order.id" class="p-2 border-b-2 border-blue-400">
                    <h3 class="font-semibold text-lg flex justify-between">
                        <span>{{ __( 'Total' ) }} : {{  nsCurrency( order.total ) }}</span>
                        <span>{{ order.code }}</span>
                    </h3>
                    <ul class="pt-2 flex -mx-1 text-sm text-gray-700">
                        <li class="px-1">{{ __( 'Discount' ) }} : {{ nsCurrency( order.discount ) }}</li>
                        <li class="px-1">{{ __( 'Status' ) }} : {{ getOrderStatus( order.payment_status ) }}</li>
                    </ul>
                </li>
            </ul>
            <div v-if="report.today_orders && report.today_orders.length === 0" class="flex items-center justify-center">
                <i class="las la-frown"></i>
            </div>
        </div>
    </div>
</template>
<script>
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'ns-cashier-dashboard',
    props: [ 'showCommission' ],
    data() {
        return {
            report: {}
        }
    },
    methods: {
        __,
        refreshReport() {
            Cashier.refreshReport();
        },
        getOrderStatus( status ) {
            switch( status ) {
                case 'paid': return __( 'Paid' ); break;
                case 'partially_paid': return __( 'Partially Paid' ); break;
                case 'unpaid': return __( 'Unpaid' ); break;
                case 'hold': return __( 'Hold' ); break;
                case 'order_void': return __( 'Void' ); break;
                case 'refunded': return __( 'Refunded' ); break;
                case 'partially_refunded': return __( 'Partially Refunded' ); break;
                default: return $status;
            }
        }
    },
    mounted() {
        Cashier.mysales.subscribe( report => {
            this.report     =   report;
        });

        const button    =   
        document.createRange().createContextualFragment(
        `<div clss="px-2">
            <div class="mr-2">
                <div 
                    id="refresh-button" 
                    class="hover:bg-white hover:text-gray-700 hover:shadow-lg hover:border-opacity-0 rounded-full h-12 w-12 cursor-pointer font-bold text-2xl justify-center items-center flex text-gray-800 border border-gray-400">
                    <i class="las la-sync-alt"></i>
                </div>             
            </div>
        </div>`);

        document.querySelector( '.top-tools-side' ).prepend( button );
        document.querySelector( '#refresh-button' ).addEventListener( 'click', () => this.refreshReport() );
    }
}
</script>