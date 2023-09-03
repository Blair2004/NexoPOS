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
            <div class="px-2">
                <div class="ns-button">
                    <button @click="selectCategories()" class="rounded flex justify-between shadow py-1 items-center text-primary px-2">
                        <i class="las la-filter text-xl"></i>
                        <span class="pl-2">{{ __( 'Categories' ) }}: {{ categoriesNames || __( 'All Categories' ) }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="selectUnits()" class="rounded flex justify-between shadow py-1 items-center text-primary px-2">
                        <i class="las la-filter text-xl"></i>
                        <span class="pl-2">{{ __( 'Units' ) }}: {{ unitsNames || __( 'All Units' ) }}</span>
                    </button>
                </div>
            </div>
        </div>
        <div id="report-printable" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-secondary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Range : {date1} &mdash; {date2}' ).replace( '{date1}', startDateField.value ).replace( '{date2}', endDateField.value ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Sold Stock Report' ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="rounded my-4">
                <div class="ns-box shadow">
                    <div class="border-b ns-box-body">
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
<script lang="ts">
import moment from "moment";
import nsDatepicker from "~/components/ns-datepicker.vue";
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { joinArray } from '~/libraries/join-array';
import { default as nsDateTimePicker } from '~/components/ns-date-time-picker.vue';
import { nsCurrency } from '~/filters/currency';
import { Popup } from "~/libraries/popup";
import NsSelectPopup from "~/popups/ns-select-popup.vue";
import { Category } from "~/interfaces/category";
import { Unit } from "~/interfaces/unit";
import { selectApiEntities } from "~/libraries/select-api-entities";

export default {
    name: 'ns-sold-stock-report',
    props: [ 'storeLogo', 'storeName' ],
    data() {
        return {
            categoriesNames: '',
            unitsNames: '',
            startDateField: {
                type: 'datetimepicker',
                value: moment( ns.date.current ).startOf( 'month' ).format( 'YYYY-MM-DD HH:mm:ss' ),
            },
            endDateField: {
                type: 'datetimepicker',
                value: moment( ns.date.current ).endOf( 'month' ).format( 'YYYY-MM-DD HH:mm:ss' ),
            },
            categoryField: {
                label: __( 'Filter by Category' ),
                value: [],
                name: 'filter_by_category'
            },
            unitField: {
                label: __( 'Filter by Unit' ),
                value: [],
                name: 'filter_by_unit'
            },
            products: [],
            ns: window[ 'ns' ]
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
        async selectCategories() {
            try {
                const response  =   await selectApiEntities( '/api/categories', __( 'Limit Results By Categories' ), this.categoryField.value );
                this.categoriesNames    =   response.labels;
                this.categoryField.value    =   response.values;
                this.loadReport();
            } catch( exception ) {
                nsSnackBar.error( __( 'An error has occured while loading the categories' ) ).subscribe();
            }
        },
        async selectUnits() {
            try {
                const response  =   await selectApiEntities( '/api/units', __( 'Limit Results By Units' ), this.unitField.value )
                this.unitsNames         =   response.labels;
                this.unitField.value    =   response.values;
                this.loadReport();
            } catch( exception ) {
                nsSnackBar.error( __( 'An error has occured while loading the units' ) ).subscribe();
            }
        },
        printSaleReport() {
            this.$htmlToPaper( 'report-printable' );
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

            nsHttpClient.post( '/api/reports/sold-stock-report', { 
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
        }
    }
}
</script>