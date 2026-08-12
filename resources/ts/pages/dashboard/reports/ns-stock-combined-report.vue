<template>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2 flex -mx-2">
                <div class="px-2">
                    <ns-field :field="datePicker"></ns-field>
                </div>
                <div class="px-2">
                    <div class="ns-button">
                        <button @click="loadReport()" class="rounded flex justify-between shadow py-1 items-center text-fontcolor px-2">
                            <i class="las la-sync-alt text-xl"></i>
                            <span class="pl-2">{{ __( 'Load' ) }}</span>
                        </button>
                    </div>
                </div>
                <div class="px-2">
                    <div class="ns-button">
                        <button @click="printSaleReport()" class="rounded flex justify-between shadow py-1 items-center text-fontcolor px-2">
                            <i class="las la-print text-xl"></i>
                            <span class="pl-2">{{ __( 'Print' ) }}</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="px-2 flex -mx-2">
                <div class="px-2">
                    <div class="ns-button">
                        <button @click="selectCategories()" class="rounded flex justify-between shadow py-1 items-center text-fontcolor px-2">
                            <i class="las la-filter text-xl"></i>
                            <span class="pl-2">{{ __( 'Categories' ) }}: {{ categoriesNames || __( 'All Categories' ) }}</span>
                        </button>
                    </div>
                </div>
                <div class="px-2">
                    <div class="ns-button">
                        <button @click="selectUnits()" class="rounded flex justify-between shadow py-1 items-center text-fontcolor px-2">
                            <i class="las la-filter text-xl"></i>
                            <span class="pl-2">{{ __( 'Units' ) }}: {{ unitsNames || __( 'All Units' ) }}</span>
                        </button>
                    </div>
                </div>
                <div class="px-2">
                    <div class="ns-button">
                        <button @click="generateReport()" class="rounded flex justify-between shadow py-1 items-center text-fontcolor px-2">
                            <i class="las la-sync-alt"></i>
                            <span class="pl-2">{{ __( 'Generate Report' ) }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="combined-report">
            <div class="flex w-full mb-4">
                <div class="flex justify-between w-full">
                    <div class="text-fontcolor-soft">
                        <ul>
                            <li class="pb-1 border-b border-dashed" v-html="__( 'Date : {date}' ).replace( '{date}', moment( datePicker.value ).format( ns.date.format ) )"></li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Combined Products History' ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="box bg-box-background">
                <div class="box-body overflow-x-auto text-fontcolor">
                    <table class="min-w-fit ns-table w-full table-auto">
                        <thead class="text-sm">
                            <tr class="font-bold">
                                <th class="border p-2 w-1/3">{{ __( 'Name' ) }}</th>
                                <th v-for="column in reportColumns" :key="column.identifier" class="border p-2">
                                    <span class="hidden md:inline-block">{{ column.label }}</span>
                                    <span class="inline-block md:hidden">{{ column.shortLabel }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-xs">
                            <tr v-for="product in products" :key="product.id">
                                <td class="border p-2">{{ product.history_name }} ({{ product.unit_name }})</td>
                                <td v-for="column in reportColumns" :key="column.identifier" class="border p-2">
                                    {{ product[column.field] }}
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td :colspan="reportColumns.length + 1" class="border p-2 text-center">{{ __( 'No data available' ) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import moment from 'moment';
import { __ } from '~/libraries/lang';
import { selectApiEntities } from "~/libraries/select-api-entities";

declare const ns, nsSnackBar, nsHttpClient, nsHooks;

export default {
    props: [ 'storeLogo', 'storeName' ],
    data() {
        const reportColumns = nsHooks.applyFilters( 'ns-stock-combined-report-columns', [
            { identifier: 'initial', label: __( 'Initial Quantity' ), shortLabel: __( 'Ini. Qty' ), field: 'history_initial_quantity' },
            { identifier: 'procured', label: __( 'Added Quantity' ), shortLabel: __( 'Add. Qty' ), field: 'history_procured_quantity' },
            { identifier: 'sold', label: __( 'Sold Quantity' ), shortLabel: __( 'Sold Qty' ), field: 'history_sold_quantity' },
            { identifier: 'defective', label: __( 'Defective Quantity' ), shortLabel: __( 'Defec. Qty' ), field: 'history_defective_quantity' },
            { identifier: 'final', label: __( 'Final Quantity' ), shortLabel: __( 'Final Qty' ), field: 'history_final_quantity' },
        ] );

        return {
            __,
            ns,
            moment,
            categoriesNames: '',
            unitsNames: '',
            categories: [],
            products: [],
            reportColumns,
            units: [],
            selectedCategories: [],
            selectedUnits: [],
            categoryField: {
                value: [],
                label: __( 'Filter by Category' ),
            },
            unitField: {
                value: [],
                label: __( 'Filter by Units' )
            },
            datePicker: {
                type: 'datetimepicker',
                name: 'date',
                value: '',
                validation: 'required'
            },
        }
    },
    methods: {
        loadReport() {
            nsHttpClient.post( '/api/reports/product-history-combined', { 
                date: this.datePicker.value,
                categories: this.categoryField.value,
                units: this.unitField.value
            }).subscribe({
                next: products => {
                    this.products     =   products;
                },
                error: ( error ) => {
                    nsSnackBar.error( error.message );
                }
            });
        },
        printSaleReport() {
            this.$htmlToPaper( 'combined-report' );
        },
        async generateReport() {
            try {
                const response:{ status: string, message: string } = await new Promise( ( resolve, reject ) => {
                    nsHttpClient.post( '/api/reports/compute-combined-report', { date: this.datePicker.value }).subscribe({
                        next: response => {
                            resolve( response );
                        }, 
                        error: response => {
                            reject( response );
                        }
                    })
                });

                nsSnackBar.success( response.message );
            } catch( response ) {
                nsSnackBar.error( response.message );
            }
        },
        async selectCategories() {
            try {
                const response              =   await selectApiEntities( '/api/categories', this.categoryField.label, this.categoryField.value );
                this.categoryField.value    =   response.values;
                this.categoriesNames          =   response.labels;
                this.loadReport();
            } catch (error) {
                if ( error !== false ) {
                    return nsSnackBar.error( __( 'An error has occurred while loading the categories' ) );
                }
            }
        },
        async selectUnits() {
            try {
                const response              =   await selectApiEntities( '/api/units', this.unitField.label, this.unitField.value );
                this.unitField.value    =   response.values;
                this.unitsNames          =   response.labels;
                this.loadReport();
            } catch (error) {
                if ( error !== false ) {
                    return nsSnackBar.error( __( 'An error has occurred while loading the units' ) );
                }
            }
        },
    }
};
</script>
