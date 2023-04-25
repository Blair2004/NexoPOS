<template>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2">
                <ns-date-time-picker :date="startDate" @change="setStartDate( $event )"></ns-date-time-picker>
            </div>
            <div class="px-2">
                <ns-date-time-picker :date="endDate" @change="setEndDate( $event )"></ns-date-time-picker>
            </div>
            <div class="px-2">
                <button @click="loadReport()" class="rounded flex justify-between bg-box-background shadow py-1 items-center text-primary px-2">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Load' ) }}</span>
                </button>
            </div>
            <div class="px-2">
                <button @click="printSaleReport()" class="rounded flex justify-between bg-box-background shadow py-1 items-center text-primary px-2">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Print' ) }}</span>
                </button>
            </div>
        </div>
        <div id="profit-report" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-secondary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Profit Report' ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="shadow rounded my-4">
                <div class="ns-box">
                    <div class="border-b ns-box-body">
                        <table class="table ns-table w-full">
                            <thead>
                                <tr>
                                    <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Unit' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Quantity' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Purchase Price' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Sale Price' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Taxes' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Profit' ) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product of products" :key="product.id" :class="product.total_price - product.total_purchase_price < 0 ? 'bg-red-100' : 'bg-white'">
                                    <td class="p-2 border border-info-primary">{{ product.name }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ product.unit_name }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ product.quantity }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ nsCurrency( product.total_purchase_price ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ nsCurrency( product.total_price ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ nsCurrency( product.tax_value ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ nsCurrency( product.total_price - product.total_purchase_price ) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="font-semibold">
                                <tr>
                                    <td colspan="2" class="p-2 border"></td>
                                    <td class="p-2 border text-right">{{ totalQuantities }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( totalPurchasePrice ) }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( totalSalePrice ) }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( totalTax ) }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( totalProfit ) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from "moment";
import nsDatepicker from "~/components/ns-datepicker.vue";
import nsDateTimePicker from "~/components/ns-date-time-picker.vue";
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'ns-profit-report',
    props: [ 'store-logo', 'store-name' ],
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            products: [],
            ns: window.ns
        }
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    computed: {
        totalQuantities() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.quantity )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalPurchasePrice() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.total_purchase_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalSalePrice() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.total_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalProfit() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.total_price - order.total_purchase_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalTax() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.tax_value )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
    },
    methods: {
        __,
        nsCurrency,
        printSaleReport() {
            this.$htmlToPaper( 'profit-report' );
        },
        setStartDate( moment ) {
            this.startDate  =   moment.format();
        },

        loadReport() {
            if ( this.startDate === null || this.endDate ===null ) {
                return nsSnackBar.error( __( 'Unable to proceed. Select a correct time range.' ) ).subscribe();
            }

            const startMoment   =   moment( this.startDate );
            const endMoment     =   moment( this.endDate );

            if ( endMoment.isBefore( startMoment ) ) {
                return nsSnackBar.error( __( 'Unable to proceed. The current time range is not valid.' ) ).subscribe();
            }

            nsHttpClient.post( '/api/reports/profit-report', { 
                startDate: this.startDate,
                endDate: this.endDate
            }).subscribe({
                next: products => {
                    this.products     =   products;
                },
                error: ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                }
            });
        },

        setEndDate( moment ) {
            this.endDate    =   moment.format();
        },
    }
}
</script>