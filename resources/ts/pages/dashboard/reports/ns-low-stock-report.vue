<template>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2">
                <div class="ns-button">
                    <button @click="loadRelevantReport()" class="rounded flex justify-between shadow py-1 items-center px-2">
                        <i class="las la-sync-alt text-xl"></i>
                        <span class="pl-2">{{ __( 'Load' ) }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="printSaleReport()" class="rounded flex justify-between shadow py-1 items-center px-2">
                        <i class="las la-print text-xl"></i>
                        <span class="pl-2">{{ __( 'Print' ) }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="selectReport()" class="rounded flex justify-between shadow py-1 items-center px-2">
                        <i class="las la-filter text-xl"></i>
                        <span class="pl-2">{{ __( 'Report Type' ) }} : {{ reportTypeName }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="selectCategories()" class="rounded flex justify-between shadow py-1 items-center px-2">
                        <i class="las la-filter text-xl"></i>
                        <span class="pl-2">{{ __( 'Categories' ) }} : {{ categoryName || __( 'All Categories' ) }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="selectUnits()" class="rounded flex justify-between shadow py-1 items-center px-2">
                        <i class="las la-filter text-xl"></i>
                        <span class="pl-2">{{ __( 'Units' ) }} : {{ unitNames || __( 'All Units' ) }}</span>
                    </button>
                </div>
            </div>
        </div>
        <div id="low-stock-report" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-primary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : {reportTypeName}' ).replace( '{reportTypeName}', reportTypeName ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="text-primary shadow rounded my-4">
                <div class="ns-box">
                    <div class="ns-box-body" v-if="reportType === 'low_stock'">
                        <table class="table ns-table w-full">
                            <thead>
                                <tr>
                                    <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                    <th class="border p-2 text-left">{{ __( 'Unit' ) }}</th>
                                    <th width="150" class="border p-2 text-right">{{ __( 'Threshold' ) }}</th>
                                    <th width="150" class="border border-info-secondary bg-info-primary p-2 text-right">{{ __( 'Quantity' ) }}</th>
                                    <th width="150" class="border border-success-secondary bg-success-primary p-2 text-right">{{ __( 'Price' ) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="products.length === 0">
                                    <td colspan="4" class="p-2 border text-center">
                                        <span>{{ __( 'There is no product to display...' ) }}</span>
                                    </td>
                                </tr>
                                <tr :key="key" v-for="(unitQuantity,key) of products" class="text-sm">
                                    <td class="p-2 border">{{ unitQuantity.product.name }}</td>
                                    <td class="p-2 border">{{ unitQuantity.unit.name }}</td>
                                    <td class="p-2 border text-right">{{ unitQuantity.low_quantity }}</td>
                                    <td class="p-2 border text-right">{{ unitQuantity.quantity }}</td>
                                    <td class="p-2 border border-success-secondary bg-success-primary text-right">{{ nsCurrency( unitQuantity.quantity * unitQuantity.sale_price ) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="ns-box-body" v-if="reportType === 'stock_report'">
                        <table class="table ns-table w-full">
                            <thead>
                                <tr>
                                    <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                    <th class="border p-2 text-left">{{ __( 'Unit' ) }}</th>
                                    <th width="150" class="border p-2 text-right">{{ __( 'Price' ) }}</th>
                                    <th width="150" class="border p-2 text-right">{{ __( 'Quantity' ) }}</th>
                                    <th width="150" class="border p-2 text-right">{{ __( 'Total Price' ) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="stockReportResult.data === undefined || stockReportResult.data.length === 0">
                                    <td colspan="5" class="p-2 border text-center">
                                        <span>{{ __( 'There is no product to display...' ) }}</span>
                                    </td>
                                </tr>
                                <template v-if="stockReportResult.data !== undefined">
                                    <template v-for="product of stockReportResult.data">
                                        <tr :key="key" v-for="(unitQuantity,key) of product.unit_quantities" class="text-sm">
                                            <td class="p-2 border">
                                                <div class="flex flex-col">
                                                    <span>{{ product.name }}</span>
                                                    <!-- <small>{{ __( 'SKU' ) }}: {{ product.sku }}</small> -->
                                                </div>
                                            </td>
                                            <td class="p-2 border">{{ unitQuantity.unit.name }}</td>
                                            <td class="p-2 border text-right">{{ nsCurrency( unitQuantity.sale_price ) }}</td>
                                            <td class="p-2 border text-right">{{ unitQuantity.quantity }}</td>
                                            <td class="p-2 border text-right">{{ nsCurrency( unitQuantity.quantity * unitQuantity.sale_price ) }}</td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="p-2 border"></td>
                                    <td class="p-2 border"></td>
                                    <td class="p-2 border"></td>
                                    <td class="p-2 border text-right">{{ sum( stockReportResult, 'quantity' ) }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( totalSum( stockReportResult, 'sale_price', 'quantity' ) ) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="flex justify-end p-2" v-if="stockReportResult.data">
                            <ns-paginate @load="loadStockReport( $event )" :pagination="stockReportResult"/>
                        </div>
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
import { default as nsDateTimePicker } from '~/components/ns-date-time-picker.vue';
import { __ } from '~/libraries/lang';
import FormValidation from '~/libraries/form-validation';
import nsSelectPopupVue from '~/popups/ns-select-popup.vue';
import nsPaginate from '~/components/ns-paginate.vue';
import { nsCurrency } from '~/filters/currency';
import { joinArray } from "~/libraries/join-array";

export default {
    name : 'ns-low-stock-report',
    props: [ 'storeLogo', 'storeName' ],
    mounted() {
        this.reportType     =   this.options[0].value;
        this.loadRelevantReport();
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
        nsPaginate,
    },
    data() {
        return {
            ns: window.ns,
            products: [],
            options: [{
                label: __( 'Stock Report' ),
                value: 'stock_report',
            },{
                label: __( 'Low Stock Report' ),
                value: 'low_stock',
            }],
            stockReportResult: {},
            reportType: '',
            reportTypeName: '',
            unitNames: '',
            categoryName: '',
            categoryIds: [],
            unitIds: [],
            validation: new FormValidation,
        }
    },
    watch: {
        reportType() {
            const result    =   this.options.filter( option => option.value === this.reportType );

            if ( result.length > 0 ) {
                this.reportTypeName     =   result[0].label;
            } else {
                this.reportTypeName     =   __( 'N/A' );
            }
        },
    },
    methods: {
        __,
        nsCurrency,
        joinArray,
        async selectReport() {
            try {
                const response     =   await new Promise( ( resolve, reject )  => {
                    Popup.show( nsSelectPopupVue, {
                        label: __( 'Report Type' ),
                        options: this.options,
                        resolve, reject
                    });
                });

                this.reportType     =   response;

                this.loadRelevantReport();
            } catch( exception ) {
                // ...
            }
        },
        async selectUnits() {
            nsHttpClient.get( '/api/units' ).subscribe({
                next: async ( units ) => {
                    try {
                        const response =        await new Promise( ( resolve, reject) => {
                            Popup.show( nsSelectPopupVue, {
                                label: __( 'Select Units' ),
                                type: 'multiselect',
                                options: units.map( unit => {
                                    return {
                                        label: unit.name,
                                        value: unit.id
                                    }
                                }),
                                resolve,
                                reject
                            })
                        });

                        const unitNames     =   units.filter( unit => response.includes( unit.id ) ).map( unit => unit.name );
                        this.unitNames      =   this.joinArray( unitNames );
                        this.unitIds        =   response;
                        
                        this.loadRelevantReport();

                    } catch( exception ) {
                        console.log( exception );
                    }
                },
                error: error => {
                    nsSnackBar.error( __( 'An error has occured while loading the units.' ) ).subscribe();
                }
            })
        },
        async selectCategories() {
            nsHttpClient.get( '/api/categories' ).subscribe({
                next: async ( categories ) => {
                    try {
                        const response =        await new Promise( ( resolve, reject) => {
                            Popup.show( nsSelectPopupVue, {
                                label: __( 'Select Categories' ),
                                type: 'multiselect',
                                options: categories.map( category => {
                                    return {
                                        label: category.name,
                                        value: category.id
                                    }
                                }),
                                resolve,
                                reject
                            })
                        });

                        const categoryName  =   categories.filter( cat => response.includes( cat.id ) ).map( cat => cat.name );
                        this.categoryName   =   this.joinArray( categoryName );
                        this.categoryIds    =   response;

                        this.loadRelevantReport();

                    } catch( exception ) {
                        console.log( exception );
                    }
                },
                error: error => {
                    nsSnackBar.error( __( 'An error has occured while loading the categories.' ) ).subscribe();
                }
            })
        },
        loadRelevantReport() {
            switch( this.reportType ) {
                case 'stock_report':
                    this.loadStockReport();
                break;
                case 'low_stock':
                    this.loadReport();
                break;
            }
        },
        printSaleReport() {
            this.$htmlToPaper( 'low-stock-report' );
        },
        loadStockReport( url = null ) {
            nsHttpClient.post( url || '/api/reports/stock-report', {
                    categories: this.categoryIds,
                    units: this.unitIds
                })
                .subscribe({
                    next: result => {
                        this.stockReportResult   =   result;
                    }, 
                    error: ( error ) => {
                        nsSnackBar
                            .error( error.message )
                            .subscribe();
                    }
                })
        },
        totalSum( result, firstKey, secondKey ) {
            if ( result.data !== undefined ) {
                const unitQuantities    =   result.data.map( product => product.unit_quantities );

                const values            =   unitQuantities.map( unitQuantities => {
                    const result    =   unitQuantities.map( unitQuantity => unitQuantity[ firstKey ] * unitQuantity[ secondKey ] );
                    
                    if ( result.length > 0 ) {
                        return result.reduce( ( a, b ) => parseFloat( a ) + parseFloat( b ) );
                    }

                    return 0;
                });

                if ( values.length > 0 ) {
                    return values.reduce( ( a, b ) => parseFloat( a ) + parseFloat( b ) );
                }
            }

            return 0;
        },
        sum( result, type ) {
            if ( result.data !== undefined ) {
                const unitQuantities    =   result.data.map( product => product.unit_quantities );
                const values            =   unitQuantities.map( unitQuantities => {
                    const result    =   unitQuantities.map( unitQuantity => unitQuantity[ type ] );
                    
                    if ( result.length > 0 ) {
                        return result.reduce( ( a, b ) => parseFloat( a ) + parseFloat( b ) );
                    }

                    return 0;
                });

                if ( values.length > 0 ) {
                    return values.reduce( ( a, b ) => parseFloat( a ) + parseFloat( b ) );
                }
            }

            return 0;
        },
        loadReport() {
            nsHttpClient.post( '/api/reports/low-stock', {
                    categories: this.categoryIds,
                    units: this.unitIds
                })
                .subscribe({
                    next: result => {
                        this.products   =   result;
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