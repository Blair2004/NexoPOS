<script>
import FormValidation from '../../libraries/form-validation';
import { Subject, BehaviorSubject, forkJoin } from "rxjs";
import { map } from "rxjs/operators";
import { nsSnackBar, nsHttpClient } from '../../bootstrap';
import NsManageProducts from './manage-products';

export default {
    name: 'ns-procurement',
    mounted() {
        this.form   =   {
            main: {
                label: 'Name',
                name: 'name',
                disabled: false
            },
            products: []
        };
        this.form   =   this.formValidation.createForm( this.form );
        this.reloadEntities();
    },
    computed: {
        activeTab() {
            return this.validTabs.filter( tab => tab.active ).length > 0 ? this.validTabs.filter( tab => tab.active )[0] : false;
        },
    },
    data() {
        return {
            /**
             * Is the total taxes
             * computed on all the supplied products
             */
            totalTaxValues: 0,
            
            /**
             * is the total purchase price of
             * all the products
             */
            totalPurchasePrice: 0,


            /**
             * Creating an instance of the form validation
             * to proceed with basic form validation
             */
            formValidation: new FormValidation,

            /**
             * Reference to the form. Contains
             * all the values and that's what is submitted
             * to the server
             */
            form: {},

            /**
             * Reference to the nsSnackBar object
             */
            nsSnackBar,

            /**
             * Is the array that contains the various 
             * procurement informations
             */
            fields: [],

            /**
             * Is the array that contains the result
             * from the search.
             */
            searchResult: [],

            /**
             * Search value.
             */
            searchValue: '',

            /**
             * Debounce reference,used for searching
             * product using the search bar
             */
            debounceSearch: null,

            /**
             * A reference to the nsHttpClient object
             */
            nsHttpClient,

            /**
             * Must contain the 
             * available taxes group
             */
            taxes: [],

            /**
             * Define the available tabs on
             * the actual uI
             */
            validTabs: [
                {
                    label: 'Details',
                    identifier: 'details',
                    active: true,
                }, {
                    label: 'Products',
                    identifier: 'products',
                    active: false,
                }, 
            ],

            /**
             * control the state of the reloading
             * spinner
             */
            reloading: false
        }
    },
    watch: {
        searchValue( value ) {
            if ( value ) {
                clearTimeout( this.debounceSearch );
                this.debounceSearch     =   setTimeout( () => {
                    this.doSearch( value );
                }, 500 );
            }
        }
    },
    components: {
        NsManageProducts
    },
    props: [ 'submit-method', 'submit-url', 'return-url', 'src', 'rules' ],
    methods: {
        computeTotal() {

            this.totalTaxValues = 0;

            if ( this.form.products.length > 0 ) {
                this.totalTaxValues = this.form.products.map( p => p.tax_value )
                    .reduce( ( b, a ) => b + a );
            }

            this.totalPurchasePrice     =   0;

            if ( this.form.products.length > 0 ) {
                this.totalPurchasePrice     =   this.form.products.map( p => parseFloat( p.total_purchase_price ) )
                    .reduce( ( b, a ) => b + a );
            }
        },

        /**
         * Ensure a line is being updated after
         * some field has been changed.
         * @param {integer} product index
         * @return {void}
         */
        updateLine( index ) {
            const product   =   this.form.products[ index ];
            const taxGroup  =   this.taxes.filter( taxGroup => taxGroup.id === product.tax_group_id );

            if ( parseFloat( product.purchase_price_edit ) > 0 && parseFloat( product.quantity ) > 0 ) {

                /**
                 * if some tax group is provided
                 * then let's compute all the grouped taxes
                 */
                if ( taxGroup.length > 0 ) {
                    const totalTaxes    =   taxGroup[0].taxes.map( tax => {
                        return ( parseFloat( tax.rate ) * product.purchase_price_edit ) / 100;
                    });

                    product.tax_value   =   ( totalTaxes.reduce( ( b, a ) => b + a ) );

                    if ( product.tax_type === 'inclusive' ) {
                        product.gross_purchase_price    =   parseFloat( product.purchase_price_edit ) - product.tax_value;
                        product.purchase_price          =   parseFloat( product.purchase_price_edit );
                        product.net_purchase_price      =   parseFloat( product.purchase_price_edit );
                    } else {
                        product.gross_purchase_price    =   parseFloat( product.purchase_price_edit );
                        product.purchase_price          =   parseFloat( product.purchase_price_edit ) + product.tax_value;
                        product.net_purchase_price      =   parseFloat( product.purchase_price_edit ) + product.tax_value;
                    }
                } else {
                    product.gross_purchase_price    =   parseFloat( product.purchase_price_edit );
                    product.purchase_price          =   parseFloat( product.purchase_price_edit );
                    product.net_purchase_price      =   parseFloat( product.purchase_price_edit );
                    product.tax_value               =   0;
                }

                product.tax_value                   =   product.tax_value * parseFloat( product.quantity );
                product.total_purchase_price        =   product.purchase_price * parseFloat( product.quantity );
            }

            this.computeTotal();
            this.$forceUpdate();
        },

        /**
         * Switch the tax type applied 
         * on the current product.
         */
        switchTaxType( product, index ) {
            product.tax_type =  product.tax_type === 'inclusive' ? 'exclusive' : 'inclusive';
            this.updateLine( index );
        },

        /**
         * Perform a seach and populate
         * the search result array
         * @param string
         * @return void
         */
        doSearch( search ) {
            nsHttpClient.post( '/api/nexopos/v4/products/search', { search })
                .subscribe( result => {
                    if ( result.length === 1 ) {
                        this.addProductList( result[0] );
                    } else {
                        this.searchResult   =   result;
                    }
                })
        },

        /**
         * Reload the value from the server.
         * Useful to reload data after having created a new
         * entity
         * @return void
         */
        reloadEntities() {
            this.reloading          =   true;
            forkJoin([
                nsHttpClient.get( '/api/nexopos/v4/categories' ),
                nsHttpClient.get( '/api/nexopos/v4/products' ),
                nsHttpClient.get( '/api/nexopos/v4/forms/ns.procurement' ),
                nsHttpClient.get( '/api/nexopos/v4/taxes/groups' ),
            ]).subscribe( result => {
                this.reloading      =   false;
                this.categories     =   result[0];
                this.products       =   result[1];
                this.form.tabs      =   result[2].tabs;
                this.taxes          =   result[3];
            })
        },
        setTabActive( tab ) {
            this.validTabs.forEach( tab => tab.active = false );
            this.$forceUpdate();
            this.$nextTick().then( () => {
                tab.active  =   true;
            });
        },
        addProductList( product ) {

            if ( product.purchase_units === undefined ) {
                return nsSnackBar.error( 'Unable to add product which doesn\'t have a default unit selected for purchase.' )
                    .subscribe();
            }

            product.gross_purchase_price        =   0;
            product.purchase_price_edit         =   0;
            product.tax_value                   =   0;
            product.net_purchase_price          =   0;
            product.purchase_price              =   0;
            product.total_price                 =   0;
            product.total_purchase_price        =   0;
            product.quantity                    =   1;
            product.tax_group_id                =   0;
            product.tax_type                    =   'inclusive';
            product.unit_id                     =   0;
            product.product_id                  =   product.id;
            product.procurement_id              =   null;
            product.$invalid                    =   false;

            this.searchResult           =   [];
            this.searchValue            =   '';

            this.form.products.push( product );
        },
        submit() {

            if ( this.form.products.length === 0 ) {
                // return nsSnackBar.error( this.$slots[ 'error-no-products' ] ? this.$slots[ 'error-no-products' ][0].text : 'No error message provided on the slot "error-no-products".', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                //     .subscribe();
            }

            this.form.products.forEach( product => {
                console.log( product );
                if ( ! parseFloat( product.quantity ) >= 1 ) {
                    product.$invalid    =   true;
                } else if ( product.unit_id === 0 ) {
                    product.$invalid    =   true;
                } else {
                    product.$invalid    =   false;
                }
            });

            const invalidProducts   =   this.form.products.filter( product => product.$invalid );

            if ( invalidProducts.length > 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-invalid-products' ] ? this.$slots[ 'error-invalid-products' ][0].text : 'No error message provided on the slot "error-invalid-products".', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                    .subscribe();
            }

            if ( this.formValidation.validateForm( this.form ).length > 0 ) {
                /**
                 * hack to force rerendering
                 * there might be a better solutin here.
                 */
                this.setTabActive( this.activeTab );
                return nsSnackBar.error( this.$slots[ 'error-invalid-form' ] ? this.$slots[ 'error-invalid-form' ][0].text : 'No error message provided for having an invalid form.', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                    .subscribe();
            }

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( this.$slots[ 'error-no-submit-url' ] ? this.$slots[ 'error-no-submit-url' ][0].text : 'No error message provided for not having a valid submit url.', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            const data  =   {
                ...this.formValidation.extractForm( this.form ),
            }

            nsHttpClient[ this.submitMethod ? this.submitMethod.toLowerCase() : 'post' ]( this.submitUrl, data )
                .subscribe( data => {
                    if ( data.status === 'success' ) {
                        return document.location   =   this.returnUrl;
                    }
                    this.formValidation.enableForm( this.form );
                }, ( error ) => {
                    nsSnackBar.error( error.message, undefined, {
                        duration: 5000
                    }).subscribe();
                    this.formValidation.triggerError( this.form, error.response.data );
                    this.formValidation.enableForm( this.form );
                })
        },
        deleteProduct( index ) {
            this.form.products.splice( index, 1 );
        },
        handleGlobalChange( event ) {
            this.globallyChecked    =   event;
            this.rows.forEach( r => r.$checked = event );
        },
    }
}
</script>
<template>
    <div class="form flex-auto flex flex-col" id="crud-form">
        <template v-if="Object.values( form ).length > 0">
            <div class="flex flex-col">
                <div class="flex justify-between items-center">
                    <label for="title" class="font-bold my-2 text-gray-700"><slot name="title">No title Provided</slot></label>
                    <div for="title" class="text-sm my-2 text-gray-700">
                        <a v-if="returnUrl" :href="returnUrl" class="rounded-full border border-gray-400 hover:bg-red-600 hover:text-white bg-white px-2 py-1">Return</a>
                    </div>
                </div>
                <div :class="form.main.disabled ? 'border-gray-500' : form.main.errors.length > 0 ? 'border-red-600' : 'border-blue-500'" class="flex border-2 rounded overflow-hidden">
                    <input v-model="form.main.value" 
                        @blur="formValidation.checkField( form.main )" 
                        @change="formValidation.checkField( form.main )" 
                        :disabled="form.main.disabled"
                        type="text" 
                        :class="form.main.disabled ? 'bg-gray-400' : ''"
                        class="flex-auto text-gray-700 outline-none h-10 px-2">
                    <button :disabled="form.main.disabled" :class="form.main.disabled ? 'bg-gray-500' : form.main.errors.length > 0 ? 'bg-red-500' : 'bg-blue-500'" @click="submit()" class="outline-none px-4 h-10 text-white border-l border-gray-400"><slot name="save">Save</slot></button>
                    <button @click="reloadEntities()" class="bg-white text-gray-700 outline-none px-4 h-10 border-gray-400"><i :class="reloading ? 'animate animate-spin' : ''" class="las la-sync"></i></button>
                </div>
                <p class="text-xs text-gray-600 py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                <p class="text-xs py-1 text-red-500" v-bind:key="index" v-for="(error, index) of form.main.errors">
                    <span><slot name="error-required">{{ error.identifier }}</slot></span>
                </p>
            </div>
            <div id="form-container" class="-mx-4 flex flex-wrap mt-4">
                <div class="px-4 w-full">
                    <div id="tabbed-card">
                        <div id="card-header" class="flex flex-wrap">
                            <div @click="setTabActive( tab )" :class="tab.active ? 'bg-white' : 'bg-gray-100'" v-for="( tab, index ) of validTabs" v-bind:key="index" class="cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg text-gray-700">
                                {{ tab.label }}
                            </div>
                        </div>
                        <div class="card-body bg-white rounded-br-lg rounded-bl-lg shadow p-2" v-if="activeTab.identifier === 'details'">
                            <div class="-mx-4 flex flex-wrap" v-if="form.tabs">
                                <div class="flex px-4 w-full md:w-1/2 lg:w-1/3" :key="index" v-for="(field, index) of form.tabs.general.fields">
                                    <ns-field :field="field"></ns-field>
                                </div>
                            </div>
                        </div>
                        <div class="card-body bg-white rounded-br-lg rounded-bl-lg shadow p-2 " v-if="activeTab.identifier === 'products'">
                            <div class="mb-2">
                                <div class="border-blue-500 flex border-2 rounded overflow-hidden">
                                    <input
                                        v-model="searchValue"
                                        type="text" 
                                        :placeholder="$slots[ 'search-placeholder' ] ? $slots[ 'search-placeholder' ][0].text : 'SKU, Barcode, Name'"
                                        class="flex-auto text-gray-700 outline-none h-10 px-2">
                                </div>
                                <div class="h-0">
                                    <div class="shadow bg-white relative z-10">
                                        <div @click="addProductList( product )" v-for="(product, index) of searchResult" :key="index" class="cursor-pointer border border-b border-gray-300 p-2 text-gray-700">
                                            <h3 class="font-bold text-gray-700">{{ product.name }}</h3>
                                            <h4 class="text-sm text-gray-600">SKU : {{ product.sku }}</h4>
                                            <h4 class="text-sm text-gray-600">Barcode : {{ product.barcode }}</h4>                                                
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr>
                                            <td class="text-gray-700 p-2 border border-gray-300 bg-gray-200">Product</td>
                                            <td class="text-gray-700 p-2 border border-gray-300 bg-gray-200 w-32">Unit Price</td>
                                            <td class="text-gray-700 p-2 border border-gray-300 bg-gray-200">Tax</td>
                                            <td class="text-gray-700 p-2 border border-gray-300 bg-gray-200">Tax Value</td>
                                            <td class="text-gray-700 p-2 border border-gray-300 bg-gray-200">UOM</td>
                                            <td class="text-gray-700 p-2 border border-gray-300 bg-gray-200">Quantity</td>
                                            <td class="text-gray-700 p-2 border border-gray-300 bg-gray-200">Total Price</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(product, index ) of form.products" :key="index" :class="product.$invalid ? 'bg-red-200 border-2 border-red-500' : 'bg-gray-100'">
                                            <td width="200" class="p-2 text-gray-600 border border-gray-300">
                                                <h3 class="font-semibold">{{ product.name }}</h3>
                                                <div class="flex -mx-1">
                                                    <span class="text-xs text-red-500 cursor-pointer underline px-1" @click="deleteProduct( index )">Delete</span>
                                                    <span @click="switchTaxType( product, index )" class="text-xs text-red-500 cursor-pointer underline px-1">Tax {{ product.tax_type === 'inclusive' ? 'Inclusive' : 'Exclusive' }}</span>
                                                </div>
                                            </td>
                                            <td class="p-2 text-gray-600 border border-gray-300">
                                                <div class="flex items-start">
                                                    <input @change="updateLine( index )" type="text" v-model="product.purchase_price_edit" class="w-24 border-2 p-2 border-blue-400 rounded">
                                                </div>
                                            </td>
                                            <td width="100" class="p-2 text-gray-600 border border-gray-300">
                                                <div class="flex items-start">
                                                    <select @change="updateLine( index )" v-model="product.tax_group_id" class="rounded border-blue-500 border-2 p-2 w-56">
                                                        <option v-for="option of taxes" :key="option.id" :value="option.id">{{ option.name }}</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td class="p-2 text-gray-600 border border-gray-300">
                                                <div class="flex items-start flex-col">
                                                    <span class="text-sm text-gray-600">{{ product.tax_value | currency }}</span>
                                                </div>
                                            </td>
                                            <td width="100" class="p-2 text-gray-600 border border-gray-300">
                                                <div class="flex items-start">
                                                    <select v-model="product.unit_id" class="rounded border-blue-500 border-2 p-2 w-56">
                                                        <option v-for="option of product.purchase_units" :key="option.id" :value="option.id">{{ option.name }}</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td class="p-2 text-gray-600 border border-gray-300">
                                                <div class="flex items-start">
                                                    <input @change="updateLine( index )" type="text" v-model="product.quantity" class="w-24 border-2 p-2 border-blue-400 rounded">
                                                </div>
                                            </td>
                                            <td class="p-2 text-gray-600 border border-gray-300">
                                                <div class="flex items-start">{{ product.total_purchase_price | currency }}</div>
                                            </td>
                                        </tr>
                                        <tr class="bg-gray-100">
                                            <td class="p-2 text-gray-600 border border-gray-300" colspan="3"></td>
                                            <td class="p-2 text-gray-600 border border-gray-300">{{ totalTaxValues | currency }}</td>
                                            <td class="p-2 text-gray-600 border border-gray-300" colspan="2"></td>
                                            <td class="p-2 text-gray-600 border border-gray-300">{{ totalPurchasePrice | currency }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>