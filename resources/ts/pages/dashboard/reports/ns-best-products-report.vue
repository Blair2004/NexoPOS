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
                    <button @click="loadReport()" class="rounded flex justify-between border-box-background text-primary shadow py-1 items-center  px-2">
                        <i class="las la-sync-alt text-xl"></i>
                        <span class="pl-2">{{ __( 'Load' ) }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="printSaleReport()" class="rounded flex justify-between border-box-background text-primary shadow py-1 items-center  px-2">
                        <i class="las la-print text-xl"></i>
                        <span class="pl-2">{{ __( 'Print' ) }}</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="flex -mx-2">
            <div class="px-2">
                <select v-model="sort" class="text-primary border-box-background shadow rounded p-2">
                    <option value="">{{ __( 'Sort Results' ) }}</option>
                    <option value="using_quantity_asc">{{ __( 'Using Quantity Ascending' ) }}</option>
                    <option value="using_quantity_desc">{{ __( 'Using Quantity Descending' ) }}</option>
                    <option value="using_sales_asc">{{ __( 'Using Sales Ascending' ) }}</option>
                    <option value="using_sales_desc">{{ __( 'Using Sales Descending' ) }}</option>
                    <option value="using_name_asc">{{ __( 'Using Name Ascending' ) }}</option>
                    <option value="using_name_desc">{{ __( 'Using Name Descending' ) }}</option>
                </select>
            </div>
        </div>
        <div id="best-products-report" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-primary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Best Products' ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="shadow rounded my-4">
                <div class="border-b ns-box">
                    <div class="ns-box-body p-2">
                        <table class="table ns-table w-full">
                            <thead class="">
                                <tr>
                                    <th class="p-2 text-left">{{ __( 'Product' ) }}</th>
                                    <th width="150" class="p-2 text-right">{{ __( 'Unit' ) }}</th>
                                    <th width="150" class="p-2 text-right">{{ __( 'Quantity' ) }}</th>
                                    <th width="150" class="p-2 text-right">{{ __( 'Value' ) }}</th>
                                    <th width="150" class="p-2 text-right">{{ __( 'Progress' ) }}</th>
                                </tr>
                            </thead>
                            <tbody class="" v-if="report">
                                <tr :key="index" :class="product.evolution === 'progress' ? 'bg-success-primary' : 'bg-error-primary'" v-for="(product, index) of report.current.products">
                                    <td class="p-2 border ">{{ product.name }}</td>
                                    <td class="p-2 border text-right">{{ product.unit_name }}</td>
                                    <td class="p-2 border text-right">
                                        <div class="flex flex-col">
                                            <span>
                                                <span>{{ product.quantity }}</span>
                                            </span>
                                            <span :class="product.evolution === 'progress' ? 'text-success-tertiary' : 'text-danger-light-tertiary'" class="text-xs">
                                                <span v-if="product.evolution === 'progress'">+</span>
                                                {{ product.quantity - product.old_quantity }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-2 border  text-right">
                                        <div class="flex flex-col">
                                            <span>{{ nsCurrency( product.total_price ) }}</span>
                                            <span :class="product.evolution === 'progress' ? 'text-success-tertiary' : 'text-danger-light-tertiary'" class="text-xs">
                                                <span v-if="product.evolution === 'progress'">+</span>
                                                {{ nsCurrency( product.total_price - product.old_total_price ) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td :class="product.evolution === 'progress' ? 'text-success-tertiary' : 'text-error-light-tertiary'" class="p-2 border  text-right">
                                        <span v-if="product.evolution === 'progress'">
                                        {{ product.difference.toFixed(2) }}% <i class="las la-arrow-up"></i>
                                        </span>
                                        <span v-if="product.evolution === 'regress'">
                                        {{ product.difference.toFixed(2) }}% <i class="las la-arrow-down"></i>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="report.current.products.length === 0">
                                    <td colspan="5">
                                        {{ __( 'No results to show.' ) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-if=" ! report">
                                <tr>
                                    <td colspan="5" class="text-center p-2">{{ __( 'Start by choosing a range and loading the report.' ) }}</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="report" class="font-semibold">
                                <tr>
                                    <td colspan="3" class="p-2 border"></td>
                                    <td class="p-2 border text-right">{{ nsCurrency( report.current.total_price ) }}</td>
                                    <td class="p-2 border text-right"></td>
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
import { default as nsDateTimePicker } from '~/components/ns-date-time-picker.vue';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';

export default {
    name : 'ns-best-products-report',
    mounted() {
    },
    components: {
        nsDatepicker,
        nsDateTimePicker
    },
    data() {
        return {
            ns: window.ns,
            startDate: moment(),
            endDate: moment(),
            report: null,
            sort : '',
        }
    },
    computed: {
        totalDebit() {
            return 0;
        },
        totalCredit() {
            return 0;
        }
    },
    props: [ 'store-logo', 'store-name' ],
    methods: {
        nsCurrency,
        __,
        setStartDate( moment ) {
            this.startDate  =   moment.format();
        },
        setEndDate( moment ) {
            this.endDate    =   moment.format();
        },
        printSaleReport() {
            this.$htmlToPaper( 'best-products-report' );
        },
        loadReport() {
            const startDate     =   moment( this.startDate );
            const endDate       =   moment( this.endDate );

            nsHttpClient.post( '/api/reports/products-report', { 
                    startDate : startDate.format( 'YYYY/MM/DD HH:mm' ), 
                    endDate : endDate.format( 'YYYY/MM/DD HH:mm' ),
                    sort: this.sort
                })
                .subscribe({
                    next: result => {
                        result.current.products     =   Object.values( result.current.products );
                        this.report     =   result;
                    }, 
                    error: ( error ) => {
                        nsSnackBar
                            .error( error.message )
                            .subscribe();
                    }
                })
        }
    }
}
</script>