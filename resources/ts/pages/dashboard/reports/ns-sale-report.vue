<template>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2">
                <ns-date-time-picker :field="startDateField"></ns-date-time-picker>
            </div>
            <div class="px-2">
                <ns-date-time-picker :field="endDateField"></ns-date-time-picker>
            </div>
            <div class="px-2">
                <button @click="loadReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                    <i :class="isLoading ? 'animate-spin' : ''" class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Load' ) }}</span>
                </button>
            </div>
        </div>
        <div class="flex -mx-2">
            <div class="px-2">
                <button @click="printSaleReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Print' ) }}</span>
                </button>
            </div>
            <div class="px-2">
                <button @click="openSettings()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                    <i class="las la-filter text-xl"></i>
                    <span class="pl-2">{{ __( 'By Type' ) }} : {{ getType( reportType.value ) }}</span>
                </button>
            </div>
            <div class="px-2">
                <button @click="openUserFiltering()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                    <i class="las la-filter text-xl"></i>
                    <span class="pl-2">{{ __( 'By User' ) }} : {{ selectedUser || __( 'All Users' ) }}</span>
                </button>
            </div>
            <div class="px-2">
                <button @click="openCategoryFiltering()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                    <i class="las la-filter text-xl"></i>
                    <span class="pl-2">{{ __( 'By Category' ) }} : {{ selectedCategory || __( 'All Category' ) }}</span>
                </button>
            </div>
        </div>
        <div id="sale-report" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-secondary">
                        <ul>
                            <li class="pb-1 border-b border-dashed" v-html="__( 'Range : {date1} &mdash; {date2}' ).replace( '{date1}', startDateField.value ).replace( '{date2}', endDateField.value )"></li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Sale Report' ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div>
                <div class="-mx-4 flex md:flex-row flex-col">
                    <div class="w-full md:w-1/2 px-4">
                        <div class="shadow rounded my-4 ns-box">
                            <div class="border-b ns-box-body">
                                <table class="table ns-table w-full">
                                    <tbody class="text-primary">
                                        <tr class="">
                                            <td width="200" class="font-semibold p-2 border text-left bg-info-secondary border-info-primary text-white">{{ __( 'Sub Total' ) }}</td>
                                            <td class="p-2 border text-right border-info-primary">{{ nsCurrency( summary.subtotal ) }}</td>
                                        </tr>
                                        <tr class="">
                                            <td width="200" class="font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white">{{ __( 'Sales Discounts' ) }}</td>
                                            <td class="p-2 border text-right border-error-primary">{{ nsCurrency( summary.sales_discounts ) }}</td>
                                        </tr>
                                        <tr class="">
                                            <td width="200" class="font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white">{{ __( 'Sales Taxes' ) }}</td>
                                            <td class="p-2 border text-right border-error-primary">{{ nsCurrency( summary.sales_taxes ) }}</td>
                                        </tr>
                                        <tr class="" v-if="summary.product_taxes > 0">
                                            <td width="200" class="font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white">{{ __( 'Product Taxes' ) }}</td>
                                            <td class="p-2 border text-right border-error-primary">{{ nsCurrency( summary.product_taxes ) }}</td>
                                        </tr>
                                        <tr class="">
                                            <td width="200" class="font-semibold p-2 border text-left bg-info-secondary border-info-primary text-white">{{ __( 'Shipping' ) }}</td>
                                            <td class="p-2 border text-right border-success-primary">{{ nsCurrency( summary.shipping ) }}</td>
                                        </tr>
                                        <tr class="">
                                            <td width="200" class="font-semibold p-2 border text-left bg-success-secondary border-success-secondary text-white">{{ __( 'Total' ) }}</td>
                                            <td class="p-2 border text-right border-success-primary">{{ nsCurrency( summary.total ) }}</td>
                                        </tr>
                                        <tr class="">
                                            <td width="200" class="font-semibold p-2 border text-left bg-info-secondary border-success-secondary text-white">{{ __( 'Cost Of Goods' ) }}</td>
                                            <td class="p-2 border text-right border-success-primary">{{ nsCurrency( summary.total_purchase_price ) }}</td>
                                        </tr>
                                        <tr class="">
                                            <td width="200" class="font-semibold p-2 border text-left bg-success-secondary border-success-secondary text-white">{{ __( 'Profit' ) }}</td>
                                            <td class="p-2 border text-right border-success-primary">{{ nsCurrency( summary.profit ) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                    </div>
                </div>
            </div>
            <div class="bg-box-background shadow rounded my-4" v-if="reportType.value === 'products_report'">
                <div class="border-b border-box-edge">
                    <table class="table ns-table w-full">
                        <thead class="text-primary">
                            <tr>
                                <th class="border p-2 text-left">{{ __( 'Products' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Quantity' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Discounts' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Cost' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Taxes' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Total' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Profit' ) }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-primary">
                            <tr v-for="product of result" :key="product.id">
                                <td class="p-2 border">{{ product.name }}</td>
                                <td class="p-2 border text-right">{{ product.quantity }}</td>
                                <td class="p-2 border text-right">{{ nsCurrency( product.discount ) }}</td>
                                <td class="p-2 border text-right">{{ nsCurrency( product.total_purchase_price ) }}</td>
                                <td class="p-2 border text-right">{{ nsCurrency( product.tax_value ) }}</td>
                                <td class="p-2 border text-right">{{ nsCurrency( product.total_price ) }}</td>
                                <td class="p-2 border text-right">{{ nsCurrency( 
                                    Math.chain( product.total_price ).subtract( 
                                        Math.chain( product.total_purchase_price ).add( product.tax_value ).done() 
                                    ).done()
                                ) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="text-primary font-semibold">
                            <tr>
                                <td class="p-2 border text-primary"></td>
                                <td class="p-2 border text-right text-primary">{{ computeTotal( result, 'quantity' ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'discount' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_purchase_price' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'tax_value' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_price' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_price' ) - computeTotal( result, 'total_purchase_price' ) - computeTotal( result, 'tax_value' ) ) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="bg-box-background shadow rounded my-4" v-if="reportType.value === 'categories_report'">
                <div class="border-b border-box-edge">
                    <table class="table ns-table w-full">
                        <thead class="text-primary">
                            <tr>
                                <th class="border p-2 text-left">{{ __( 'Category' ) }}</th>
                                <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                <th width="100" class="border p-2">{{ __( 'Quantity' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Discounts' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Taxes' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Total' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Purchase Price' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Profit' ) }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-primary">
                            <template v-for="(category, categoryIndex) of result" :key="categoryIndex">
                                <template v-if="category.products.length > 0">
                                    <tr v-for="(product) of category.products" :key="parseInt( category.id + '' + product.id )">
                                        <td class="p-2 border">{{ category.name }}</td>
                                        <td class="p-2 border">{{ product.name }}</td>
                                        <td class="p-2 border text-right">{{ product.quantity }}</td>
                                        <td class="p-2 border text-right">{{ nsCurrency( product.discount ) }}</td>
                                        <td class="p-2 border text-right">{{ nsCurrency( product.tax_value ) }}</td>
                                        <td class="p-2 border text-right">{{ nsCurrency( product.total_price ) }}</td>
                                        <td class="p-2 border text-right">{{ nsCurrency( product.total_purchase_price ) }}</td>
                                        <td class="p-2 border text-right">{{ nsCurrency( 
                                            product.total_price - 
                                            (
                                                product.total_purchase_price + 
                                                product.tax_value + 
                                                product.discount
                                            ) 
                                        ) }}</td>
                                    </tr>
                                </template>
                                <tr class="bg-info-primary">
                                    <td colspan="2" class="p-2 border border-info-secondary">{{ category.name }}</td>
                                    <td class="p-2 border text-right border-info-secondary">{{ computeTotal( category.products, 'quantity' ) }}</td>
                                    <td class="p-2 border text-right border-info-secondary">{{ nsCurrency( computeTotal( category.products, 'discount' ) ) }}</td>
                                    <td class="p-2 border text-right border-info-secondary">{{ nsCurrency( computeTotal( category.products, 'tax_value' ) ) }}</td>
                                    <td class="p-2 border text-right border-info-secondary">{{ nsCurrency( computeTotal( category.products, 'total_price' ) ) }}</td>
                                    <td class="p-2 border text-right border-info-secondary">{{ nsCurrency( computeTotal( category.products, 'total_purchase_price' ) ) }}</td>
                                    <td class="p-2 border text-right border-info-secondary">{{ nsCurrency( 
                                        computeTotal( category.products, 'total_price' ) -
                                        (
                                            computeTotal( category.products, 'total_purchase_price' ) +
                                            computeTotal( category.products, 'tax_value' ) +
                                            computeTotal( category.products, 'discount' )
                                        )
                                    ) }}</td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="text-primary font-semibold">
                            <tr>
                                <td colspan="2" class="p-2 border text-primary"></td>
                                <td class="p-2 border text-right text-primary">{{ computeTotal( result, 'total_sold_items' ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_discount' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_tax_value' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_price' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_purchase_price' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( 
                                    computeTotal( result, 'total_price' ) -
                                    (
                                        computeTotal( result, 'total_purchase_price' )  +
                                        computeTotal( result, 'total_discount' ) +
                                        computeTotal( result, 'total_tax_value' )
                                    )
                                ) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="bg-box-background shadow rounded my-4" v-if="reportType.value === 'categories_summary'">
                <div class="border-b border-box-edge">
                    <table class="table ns-table w-full">
                        <thead class="text-primary">
                            <tr>
                                <th class="border p-2 text-left">{{ __( 'Category' ) }}</th>
                                <th width="100" class="border p-2">{{ __( 'Quantity' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Discounts' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Cost' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Taxes' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Total' ) }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-primary">
                            <template v-for="(category, categoryIndex) of result" :key="categoryIndex">
                                <tr class="">
                                    <td class="p-2 border text-left border-info-primary">{{ category.name }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ computeTotal( category.products, 'quantity' ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ nsCurrency( computeTotal( category.products, 'discount' ) ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ nsCurrency( computeTotal( category.products, 'total_purchase_price' ) ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ nsCurrency( computeTotal( category.products, 'tax_value' ) ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">{{ nsCurrency( computeTotal( category.products, 'total_price' ) ) }}</td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="text-primary font-semibold">
                            <tr>
                                <td class="p-2 border text-primary"></td>
                                <td class="p-2 border text-right text-primary">{{ computeTotal( result, 'total_sold_items' ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_discount' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_purchase_price' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_tax_value' ) ) }}</td>
                                <td class="p-2 border text-right text-primary">{{ nsCurrency( computeTotal( result, 'total_price' ) ) }}</td>
                            </tr>
                        </tfoot>
                    </table>
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
import { __ } from '~/libraries/lang';
import nsSelectPopupVue from '~/popups/ns-select-popup.vue';
import { nsCurrency } from '~/filters/currency';
import { joinArray } from "~/libraries/join-array";

export default {
    name: 'ns-sale-report',
    data() {
        return {
            saleReport: '',
            startDateField: {
                name: 'start_date',
                type: 'datetime',
                value: ns.date.moment.startOf( 'day' ).format()
            },
            endDateField: {
                name: 'end_date',
                type: 'datetime',
                value: ns.date.moment.endOf( 'day' ).format()
            },
            result: [],
            isLoading: false,
            users: [],
            ns: window.ns,
            summary: {},
            selectedUser: '',
            selectedCategory: '',
            reportType: {
                label: __( 'Report Type' ),
                name: 'reportType',
                type: 'select',
                value: 'categories_report',
                options: [
                    {
                        label: __( 'Categories Detailed' ),
                        value: 'categories_report',
                    }, {
                        label: __( 'Categories Summary' ),
                        value: 'categories_summary',
                    }, {
                        label: __( 'Products' ),
                        value: 'products_report',
                    }
                ],
                description: __( 'Allow you to choose the report type.' ),
            },
            filterUser: {
                label: __( 'Filter User' ),
                name: 'filterUser',
                type: 'select',
                value: '',
                options: [
                    // ...
                ],
                description: __( 'Allow you to choose the report type.' ),
            },
            filterCategory: {
                label: __( 'Filter By Category' ),
                name: 'filterCategory',
                type: 'multiselect',
                value: '',
                options: [
                    // ...
                ],
                description: __( 'Allow you to choose the category.' ),
            },
            field: {
                type: 'datetimepicker',
                value: '2021-02-07',
                name: 'date'
            }
        }
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    computed: {
        // ...
    },
    methods: {
        __,
        nsCurrency,
        joinArray,
        printSaleReport() {
            this.$htmlToPaper( 'sale-report' );
        },

        async openSettings() {
            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, {
                        ...this.reportType,
                        resolve, 
                        reject
                    });
                });

                this.reportType.value   =   result;
                this.result             =   [];
                this.loadReport();
            } catch( exception ) {
                console.log({ exception })
            }
        },

        async openUserFiltering() {
            try {
                /**
                 * let's try to pull the users first.
                 */
                this.isLoading  =   true;
                const result    =   await new Promise( ( resolve, reject ) => {
                    nsHttpClient.get( `/api/users` )
                        .subscribe({
                            next: (users) => {
                                this.users      =   users;
                                this.isLoading  =   false;

                                this.filterUser.options     =   [
                                    {
                                        label: __( 'All Users' ),
                                        value: ''
                                    },
                                    ...this.users.map( user => {
                                        return {
                                            label: user.username,
                                            value: user.id
                                        }
                                    })
                                ];

                                Popup.show( nsSelectPopupVue, {
                                    ...this.filterUser,
                                    resolve, 
                                    reject
                                });
                            },
                            error: error => {
                                this.isLoading  =   false;
                                nsSnackBar.error( __( 'No user was found for proceeding the filtering.' ) );
                                reject( error );
                            }
                        });
                });  
                
                const searchUser  =   this.users.filter( __user => __user.id === result );

                if ( searchUser.length > 0 ) {
                    let user    =   searchUser[0];
                    this.selectedUser       =   `${user.username} ${(user.first_name || user.last_name) ? user.first_name + ' ' + user.last_name : '' }`;
                    this.filterUser.value   =   result;
                    this.result             =   [];
                    this.loadReport();
                }
            } catch( exception ) {
                console.log({ exception });
            }
        },

        async openCategoryFiltering() {
            try {
                let categories  =   [];

                this.isLoading  =   true;
                const result    =   await new Promise( ( resolve, reject ) => {
                    nsHttpClient.get( `/api/categories` )
                        .subscribe({
                            next: (retreivedCategories) => {
                                this.isLoading  =   false;
                                categories  =   retreivedCategories;
                                this.filterCategory.options     =   [
                                    ...retreivedCategories.map( category => {
                                        return {
                                            label: category.name,
                                            value: category.id
                                        }
                                    })
                                ];

                                Popup.show( nsSelectPopupVue, {
                                    ...this.filterCategory,
                                    resolve, 
                                    reject
                                });
                            },
                            error: error => {
                                this.isLoading  =   false;
                                nsSnackBar.error( __( 'No category was found for proceeding the filtering.' ) );
                                reject( error );
                            }
                        });
                });

                if ( result.length > 0 ) {
                    let categoryNames   =   categories
                        .filter( category => result.includes( category.id ) )
                        .map( category => category.name );

                    this.selectedCategory       =   this.joinArray( categoryNames );
                    this.filterCategory.value   =   result;
                } else {
                    this.selectedCategory       =   '';
                    this.filterCategory.value   =   [];
                }

                this.result             =   [];
                this.loadReport();

            } catch( exception ) {
                // ...
                console.log( exception );
            }
        },

        getType( type ) {
            const option    =   this.reportType.options.filter( option => {
                return option.value === type;
            });

            if ( option.length > 0 ) {
                return option[0].label;
            }

            return __( 'Unknown' );
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

            this.isLoading  =   true;
            nsHttpClient.post( '/api/reports/sale-report', { 
                startDate: this.startDateField.value,
                endDate: this.endDateField.value,
                type: this.reportType.value,
                user_id: this.filterUser.value,
                categories_id: this.filterCategory.value
            }).subscribe({
                next: response => {
                    this.isLoading  =   false;
                    this.result     =   response.result;
                    this.summary    =   response.summary;
                }, 
                error : ( error ) => {
                    this.isLoading  =   false;
                    nsSnackBar.error( error.message ).subscribe();
                }
            });
        },

        computeTotal( collection, attribute ) {
            if ( collection.length > 0 ) {
                return collection.map( entry => parseFloat( entry[ attribute ] ) )
                    .reduce( ( b, a ) => b + a );
            }

            return 0;
        },
    },
    props: [ 'storeLogo', 'storeName' ],
    mounted() {
        // ...
    }
}
</script>