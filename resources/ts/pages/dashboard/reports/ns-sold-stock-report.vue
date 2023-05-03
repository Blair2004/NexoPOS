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
                <div class="ns-button">
                    <button @click="loadReport()" class="rounded flex justify-between shadow py-1 items-center text-primary px-2">
                        <i class="las la-sync-alt text-xl"></i>
                        <span class="pl-2">{{ __( 'Load' ) }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="printSaleReport()" class="rounded flex justify-between shadow py-1 items-center text-primary px-2">
                        <i class="las la-print text-xl"></i>
                        <span class="pl-2">{{ __( 'Print' ) }}</span>
                    </button>
                </div>
            </div>
        </div>
        <div id="report-printable" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-secondary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Sold Stock Report' ) }}</li>
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
                    <div class="border-b ns-box-body p-2">
                        <table class="table ns-table w-full">
                            <thead class="">
                                <tr>
                                    <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Unit' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Quantity' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Tax Value' ) }}</th>
                                    <th width="150" class="text-right border p-2">{{ __( 'Total' ) }}</th>
                                </tr>
                            </thead>
                            <tbody class="">
                                <tr v-for="product of products" :key="product.id">
                                    <td class="p-2 border">{{ product.name }}</td>
                                    <td class="p-2 border text-right">{{ product.unit_name }}</td>
                                    <td class="p-2 border text-right">{{ product.quantity }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( product.tax_value ) }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( product.total_price ) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class=" font-semibold">
                                <tr>
                                    <td colspan="2" class="p-2 border"></td>
                                    <td class="p-2 border text-right">{{ totalQuantity }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( totalTaxes ) }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( totalPrice ) }}</td>
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
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { default as nsDateTimePicker } from '~/components/ns-date-time-picker.vue';
import { nsCurrency } from '~/filters/currency';


export default {
    name: 'ns-sold-stock-report',
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
        totalQuantity() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.quantity )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalTaxes() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.tax_value )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalPrice() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.total_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
    },
    methods: {
        __,
        nsCurrency,
        printSaleReport() {
            this.$htmlToPaper( 'report-printable' );
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

            nsHttpClient.post( '/api/reports/sold-stock-report', { 
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