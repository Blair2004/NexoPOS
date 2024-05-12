<template>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2">
                <ns-field :field="startDateField"></ns-field>
            </div>
            <div class="px-2">
                <ns-field :field="endDateField"></ns-field>
            </div>
            <div class="px-2">
                <button @click="loadReport()" class="rounded flex justify-between bg-input-background hover:bg-input-button-hover shadow py-1 items-center text-primary px-2">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Load' ) }}</span>
                </button>
            </div>
            <div class="px-2">
                <button @click="printSaleReport()" class="rounded flex justify-between bg-input-background hover:bg-input-button-hover shadow py-1 items-center text-primary px-2">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Print' ) }}</span>
                </button>
            </div>
            <div class="px-2">
                <button @click="selectCategories()" class="rounded flex justify-between bg-input-background hover:bg-input-button-hover shadow py-1 items-center text-primary px-2">
                    <i class="las la-filter text-xl"></i>
                    <span class="pl-2">{{ __( 'Category' ) }}: {{ categoryNames || __( 'All Categories' ) }}</span>
                </button>
            </div>
            <div class="px-2">
                <button @click="selectUnit()" class="rounded flex justify-between bg-input-background hover:bg-input-button-hover shadow py-1 items-center text-primary px-2">
                    <i class="las la-filter text-xl"></i>
                    <span class="pl-2">{{ __( 'Unit' ) }}: {{ unitNames || __( 'All Units' ) }}</span>
                </button>
            </div>
        </div>
        <div id="profit-report" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-secondary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Range : {date1} &mdash; {date2}' ).replace( '{date1}', startDateField.value ).replace( '{date2}', endDateField.value ) }}</li>
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
                                    <th width="110" class="text-right border p-2">{{ __( 'Unit' ) }}</th>
                                    <th width="110" class="text-right border p-2">{{ __( 'Quantity' ) }}</th>
                                    <th width="110" class="text-right border p-2">{{ __( 'COGS' ) }}</th>
                                    <th width="110" class="text-right border p-2">{{ __( 'Sale Price' ) }}</th>
                                    <th width="110" class="text-right border p-2">{{ __( 'Taxes' ) }}</th>
                                    <th width="110" class="text-right border p-2">{{ __( 'Profit' ) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product of products" :key="product.id" :class="product.total_price - product.total_purchase_price < 0 ? 'bg-error-primary' : 'bg-box-background'">
                                    <td class="p-2 border border-box-edge">{{ product.name }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ product.unit_name }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ product.quantity }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ nsCurrency( product.total_purchase_price ) }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ nsCurrency( product.total_price ) }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ nsCurrency( product.tax_value ) }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ nsCurrency( math.chain( product.total_price )
                                        .subtract( 
                                            math.chain( product.total_purchase_price ).add( product.tax_value ).done()
                                        ).done()
                                    ) }}</td>
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
<script lang="ts">
import moment from "moment";
import nsDatepicker from "~/components/ns-datepicker.vue";
import nsDateTimePicker from "~/components/ns-date-time-picker.vue";
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';
import { selectApiEntities } from "~/libraries/select-api-entities";
import * as math from "mathjs"

export default {
    name: 'ns-profit-report',
    props: [ 'storeLogo', 'storeName' ],
    data() {
        return {
            categoryNames: '',
            unitNames: '',
            startDateField: {
                type: 'datetimepicker',
                value: moment( ns.date.current ).startOf( 'month' ).format( 'YYYY-MM-DD HH:mm:ss' ),
            },
            endDateField: {
                type: 'datetimepicker',
                value: moment( ns.date.current ).endOf( 'month' ).format( 'YYYY-MM-DD HH:mm:ss' ),
            },
            categoryField: {
                value: [],
                label: __( 'Filter by Category' ),
            },
            unitField: {
                value: [],
                label: __( 'Filter by Units' )
            },
            products: [],
            ns: window[ 'ns' ],
            math,
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
                    .map( product => product.quantity )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalPurchasePrice() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.total_purchase_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalSalePrice() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.total_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalProfit() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => {
                        return math.chain( product.total_price ).subtract( 
                            math.chain( product.total_purchase_price ).add( product.tax_value ).done()
                        )
                    })
                    .reduce( ( b, a ) => b + a )
            }
            return 0;
        },
        totalTax() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.tax_value )
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

        async selectCategories() {
            try {
                const response              =   await selectApiEntities( '/api/categories', this.categoryField.label, this.categoryField.value );
                this.categoryField.value    =   response.values;
                this.categoryNames          =   response.labels;
                this.loadReport();
            } catch (error) {
                if ( error !== false ) {
                    return nsSnackBar.error( __( 'An error has occured while loading the categories' ) ).subscribe();
                }
            }
        },
        async selectUnit() {
            try {
                const response              =   await selectApiEntities( '/api/units', this.unitField.label, this.unitField.value );
                this.unitField.value    =   response.values;
                this.unitNames          =   response.labels;
                this.loadReport();
            } catch (error) {
                if ( error !== false ) {
                    return nsSnackBar.error( __( 'An error has occured while loading the units' ) ).subscribe();
                }
            }
        },

        loadReport() {
            if ( this.startDateField.value === null || this.endDateField.value ===null ) {
                return nsSnackBar.error( __( 'Unable to proceed. Select a correct time range.' ) ).subscribe();
            }

            const startMoment   =   moment( this.startDateField.value );
            const endMoment     =   moment( this.endDateField.value );

            if ( endMoment.isBefore( startMoment ) ) {
                return nsSnackBar.error( __( 'Unable to proceed. The current time range is not valid.' ) ).subscribe();
            }

            nsHttpClient.post( '/api/reports/profit-report', { 
                startDate: this.startDateField.value,
                endDate: this.endDateField.value,
                categories: this.categoryField.value,
                units: this.unitField.value
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