<script>
import FormValidation from '@/libraries/form-validation';
import { Subject, BehaviorSubject, forkJoin } from "rxjs";
import { map } from "rxjs/operators";
import { nsSnackBar, nsHttpClient } from '@/bootstrap';
import NsManageProducts from './manage-products';
import { Tax } from "@/libraries/tax";
import nsProcurementProductOptionsVue from '@/popups/ns-procurement-product-options.vue';
import { __ } from '@/libraries/lang';
export default {
    name: 'ns-procurement',
    mounted() {
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
                    label: __( 'Details' ),
                    identifier: 'details',
                    active: true,
                }, {
                    label: __( 'Products' ),
                    identifier: 'products',
                    active: false,
                }, 
            ],

            /**
             * control the state of the reloading
             * spinner
             */
            reloading: false,
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
        __,

        computeTotal() {

            this.totalTaxValues = 0;

            if ( this.form.products.length > 0 ) {
                this.totalTaxValues = this.form.products.map( p => p.procurement.tax_value )
                    .reduce( ( b, a ) => b + a );
            }

            this.totalPurchasePrice     =   0;

            if ( this.form.products.length > 0 ) {
                this.totalPurchasePrice     =   this.form.products.map( p => parseFloat( p.procurement.total_purchase_price ) )
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
            const taxGroup  =   this.taxes.filter( taxGroup => taxGroup.id === product.procurement.tax_group_id );

            if ( parseFloat( product.procurement.purchase_price_edit ) > 0 && parseFloat( product.procurement.quantity ) > 0 ) {

                /**
                 * if some tax group is provided
                 * then let's compute all the grouped taxes
                 */
                if ( taxGroup.length > 0 ) {
                    const totalTaxes    =   taxGroup[0].taxes.map( tax => {
                        return Tax.getTaxValue(
                            product.procurement.tax_type,
                            product.procurement.purchase_price_edit,
                            parseFloat( tax.rate )
                        );
                    });

                    product.procurement.tax_value               =   ( totalTaxes.reduce( ( b, a ) => b + a ) );

                    if ( product.procurement.tax_type === 'inclusive' ) {
                        product.procurement.net_purchase_price      =   parseFloat( product.procurement.purchase_price_edit ) - product.procurement.tax_value;
                        product.procurement.gross_purchase_price    =   parseFloat( product.procurement.purchase_price_edit );
                        product.procurement.purchase_price          =   parseFloat( product.procurement.gross_purchase_price );
                    } else {
                        product.procurement.gross_purchase_price    =   parseFloat( product.procurement.purchase_price_edit ) + product.procurement.tax_value;
                        product.procurement.net_purchase_price      =   parseFloat( product.procurement.purchase_price_edit );
                        product.procurement.purchase_price          =   parseFloat( product.procurement.gross_purchase_price );
                    }

                } else {
                    product.procurement.gross_purchase_price    =   parseFloat( product.procurement.purchase_price_edit );
                    product.procurement.purchase_price          =   parseFloat( product.procurement.purchase_price_edit );
                    product.procurement.net_purchase_price      =   parseFloat( product.procurement.purchase_price_edit );
                    product.procurement.tax_value               =   0;
                }

                product.procurement.tax_value                   =   product.procurement.tax_value * parseFloat( product.procurement.quantity );
                product.procurement.total_purchase_price        =   product.procurement.purchase_price * parseFloat( product.procurement.quantity );
            } 

            this.computeTotal();
            this.$forceUpdate();
        },

        fetchLastPurchasePrice( index ) {
            const product   =   this.form.products[ index ];
            const unit      =   product.unit_quantities.filter( unitQuantity => {
                return product.procurement.unit_id === unitQuantity.unit_id;
            });

            if ( unit.length > 0 ) {
                product.procurement.purchase_price_edit      =   ( unit[0].last_purchase_price || 0 );
            }

            this.updateLine( index );
        },

        /**
         * Switch the tax type applied 
         * on the current product.
         */
        switchTaxType( product, index ) {
            product.procurement.tax_type =  product.procurement.tax_type === 'inclusive' ? 'exclusive' : 'inclusive';
            this.updateLine( index );
        },

        /**
         * Perform a seach and populate
         * the search result array
         * @param string
         * @return void
         */
        doSearch( search ) {
            nsHttpClient.post( '/api/nexopos/v4/procurements/products/search-product', { search })
                .subscribe( result => {
                    if ( result.length === 1 ) {
                        this.addProductList( result[0] );
                    } else if ( result.length > 1 ) {
                        this.searchResult   =   result;
                    } else {
                        nsSnackBar.error( __( 'No result match your query.' ) ).subscribe();
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
                nsHttpClient.get( this.src ),
                nsHttpClient.get( '/api/nexopos/v4/taxes/groups' ),
            ]).subscribe( result => {
                this.reloading      =   false;
                this.categories     =   result[0];
                this.products       =   result[1];
                this.taxes          =   result[3];

                if ( this.form.general ) {
                    result[2].tabs.general.fieds.forEach( (field,index) => {
                        field.value     =   this.form.tabs.general.fields[ index ].value || '';
                    });
                } 

                this.form           =   Object.assign( JSON.parse( JSON.stringify( result[2] ) ), this.form );
                this.form           =   this.formValidation.createForm( this.form );

                /**
                 * if the fields are existing, we'll just
                 * make sure to update with the new provided options
                 */
                if ( this.form.tabs ) {
                    this.form.tabs.general.fields.forEach( (field, index) => {
                        if ( field.options ) {
                            field.options   =   result[2].tabs.general.fields[ index ].options;
                        }
                    });
                }

                if ( this.form.products.length === 0 ) {
                    /**
                     * if the product has been provided by the
                     * server we need to format it.
                     */
                    this.form.products  =   this.form.products.map( product => {
                        [
                            'gross_purchase_price',
                            'purchase_price_edit',
                            'tax_value',
                            'net_purchase_price',
                            'purchase_price',
                            'total_price',
                            'total_purchase_price',
                            'quantity',
                            'tax_group_id',
                        ].forEach( field => {
                            if ( product[ field ] === undefined ) {
                                product[ field ]    =   product[ field ] === undefined ? 0 : product[ field ];
                            }
                        });

                        product.$invalid                =   product.$invalid || false;
                        product.purchase_price_edit     =   product.purchase_price;
                        
                        return {
                            name: product.name,
                            purchase_units: product.purchase_units,
                            procurement: product,
                            unit_quantities: product.unit_quantities || []
                        }
                    });
                }
                
                this.$forceUpdate();
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

            if ( product.unit_quantities === undefined ) {
                return nsSnackBar.error( __( 'Unable to add product which doesn\'t unit quantities defined.' ) )
                    .subscribe();
            }

            product.procurement                             =   new Object;
            product.procurement.gross_purchase_price        =   0;
            product.procurement.purchase_price_edit         =   0;
            product.procurement.tax_value                   =   0;
            product.procurement.net_purchase_price          =   0;
            product.procurement.purchase_price              =   0;
            product.procurement.total_price                 =   0;
            product.procurement.total_purchase_price        =   0;
            product.procurement.quantity                    =   1;
            product.procurement.expiration_date             =   null;
            product.procurement.tax_group_id                =   0;
            product.procurement.tax_type                    =   'inclusive';
            product.procurement.unit_id                     =   0;
            product.procurement.product_id                  =   product.id;
            product.procurement.procurement_id              =   null;
            product.procurement.$invalid                    =   false;

            this.searchResult           =   [];
            this.searchValue            =   '';

            this.form.products.push( product );
        },
        submit() {

            if ( this.form.products.length === 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-no-products' ] ? this.$slots[ 'error-no-products' ][0].text : __( 'Unable to proceed, no product were provided.' ), this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : __( 'OK' ) )
                    .subscribe();
            }

            this.form.products.forEach( product => {
                if ( ! parseFloat( product.procurement.quantity ) >= 1 ) {
                    product.procurement.$invalid    =   true;
                } else if ( product.procurement.unit_id === 0 ) {
                    product.procurement.$invalid    =   true;
                } else {
                    product.procurement.$invalid    =   false;
                }
            });

            const invalidProducts   =   this.form.products.filter( product => product.procurement.$invalid );

            if ( invalidProducts.length > 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-invalid-products' ] ? this.$slots[ 'error-invalid-products' ][0].text : __( 'Unable to proceed, one or more product has incorrect values.' ), this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : __( 'OK' ) )
                    .subscribe();
            }

            if ( this.formValidation.validateForm( this.form ).length > 0 ) {
                /**
                 * hack to force rerendering
                 * there might be a better solutin here.
                 */
                this.setTabActive( this.activeTab );

                return nsSnackBar.error( this.$slots[ 'error-invalid-form' ] ? this.$slots[ 'error-invalid-form' ][0].text : __( 'Unable to proceed, the procurement form is not valid.' ), this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : __( 'OK' ) )
                    .subscribe();
            }

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( this.$slots[ 'error-no-submit-url' ] ? this.$slots[ 'error-no-submit-url' ][0].text : __( 'Unable to submit, no valid submit URL were provided.' ), this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : __( 'OK' ) )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            const data  =   {
                ...this.formValidation.extractForm( this.form ), ...{
                    products: this.form.products.map( product => product.procurement )
                }
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

                    this.formValidation.enableForm( this.form );
                    
                    if ( error.errors ) {
                        this.formValidation.triggerError( this.form, error.errors );
                    }
                })
        },
        deleteProduct( index ) {
            this.form.products.splice( index, 1 );
            this.$forceUpdate();
        },
        handleGlobalChange( event ) {
            this.globallyChecked    =   event;
            this.rows.forEach( r => r.$checked = event );
        },

        setProductOptions( index ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsProcurementProductOptionsVue, {
                    product: this.form.products[ index ],
                    resolve, 
                    reject
                })
            });
            
            promise.then( value => {
                for( let key in value ) {
                    this.form.products[ index ].procurement[ key ]      =   value[ key ];
                }

                this.updateLine( index );
            });
        }
    }
}
</script>
<template>
    <div class="form flex-auto flex flex-col" id="crud-form">
        <template v-if="form.main">
            <div class="flex flex-col">
                <div class="flex justify-between items-center">
                    <label for="title" class="font-bold my-2 text-primary">{{ form.main.label || __( 'No title is provided' ) }}</label>
                    <div for="title" class="text-sm my-2 text-primary">
                        <a v-if="returnUrl" :href="returnUrl" class="rounded-full ns-inset-button border px-2 py-1">{{ __( 'Go Back' ) }}</a>
                    </div>
                </div>
                <div :class="form.main.disabled ? 'disabled' : ( form.main.errors.length > 0 ? 'error' : '' )" class="flex border-2 rounded input-group info overflow-hidden">
                    <input v-model="form.main.value" 
                        @blur="formValidation.checkField( form.main )" 
                        @change="formValidation.checkField( form.main )" 
                        :disabled="form.main.disabled"
                        type="text" 
                        :class="form.main.disabled ? '' : ''"
                        class="flex-auto outline-none h-10 px-2">
                    <button :disabled="form.main.disabled"  @click="submit()" class="outline-none px-4 h-10 border-l"><slot name="save">{{ __( 'Save' ) }}</slot></button>
                    <button @click="reloadEntities()" class="outline-none px-4 h-10"><i :class="reloading ? 'animate animate-spin' : ''" class="las la-sync"></i></button>
                </div>
                <p class="text-xs text-primary py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                <p class="text-xs py-1 text-error-primary" v-bind:key="index" v-for="(error, index) of form.main.errors">
                    <span><slot name="error-required">{{ error.identifier }}</slot></span>
                </p>
            </div>
            <div id="form-container" class="-mx-4 flex flex-wrap mt-4">
                <div class="px-4 w-full">
                    <div id="tabbed-card" class="ns-tab">
                        <div id="card-header" class="flex flex-wrap">
                            <div @click="setTabActive( tab )" :class="tab.active ? 'active' : 'inactive'" v-for="( tab, index ) of validTabs" v-bind:key="index" class="tab cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg text-primary">
                                {{ tab.label }}
                            </div>
                        </div>
                        <div class="card-body ns-tab-item rounded-br-lg rounded-bl-lg shadow p-2" v-if="activeTab.identifier === 'details'">
                            <div class="-mx-4 flex flex-wrap" v-if="form.tabs">
                                <div class="flex px-4 w-full md:w-1/2 lg:w-1/3" :key="index" v-for="(field, index) of form.tabs.general.fields">
                                    <ns-field :field="field"></ns-field>
                                </div>
                            </div>
                        </div>
                        <div class="card-body ns-tab-item rounded-br-lg rounded-bl-lg shadow p-2 " v-if="activeTab.identifier === 'products'">
                            <div class="mb-2">
                                <div class="input-group info flex border-2 rounded overflow-hidden">
                                    <input
                                        v-model="searchValue"
                                        type="text" 
                                        :placeholder="$slots[ 'search-placeholder' ] ? $slots[ 'search-placeholder' ][0].text : 'SKU, Barcode, Name'"
                                        class="flex-auto text-primary outline-none h-10 px-2">
                                </div>
                                <div class="h-0">
                                    <div class="shadow bg-floating-menu relative z-10">
                                        <div @click="addProductList( product )" v-for="(product, index) of searchResult" :key="index" class="cursor-pointer border border-b hover:bg-floating-menu-hover border-floating-menu-edge p-2 text-primary">
                                            <span class="block font-bold text-primary">{{ product.name }}</span>
                                            <span class="block text-sm text-priamry">{{ __( 'SKU' ) }} : {{ product.sku }}</span>
                                            <span class="block text-sm text-primary">{{ __( 'Barcode' ) }} : {{ product.barcode }}</span>                                                
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full ns-table">
                                    <thead>
                                        <tr>
                                            <td v-for="( column, key ) of form.columns" width="200" :key="key" class="text-primary p-2 border">{{ column.label }}</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="( product, index ) of form.products" :key="index" :class="product.procurement.$invalid ? 'error border-2 border-error-primary' : ''">
                                            <template v-for="( column, key ) of form.columns" >
                                                <td :key="key" v-if="column.type === 'name'" class="p-2 text-primary border">
                                                    <span class="font-semibold">{{ product.name }}</span>
                                                    <div class="flex justify-between">
                                                        <div class="flex -mx-1 flex-col">
                                                            <div class="px-1">
                                                                <span class="text-xs text-error-primary cursor-pointer underline px-1" @click="deleteProduct( index )">{{ __( 'Delete' ) }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex -mx-1 flex-col">
                                                            <div class="px-1">
                                                                <span class="text-xs text-error-primary cursor-pointer underline px-1" @click="setProductOptions( index )">{{ __( 'Options' ) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td :key="key" v-if="column.type === 'text'" class="p-2 w-3 text-primary border">
                                                    <div class="flex items-start">
                                                        <div class="input-group rounded border-2">
                                                            <input @change="updateLine( index )" type="text" v-model="product.procurement[ key ]" class="w-24 p-2">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td :key="key" v-if="column.type === 'tax_group_id'" class="p-2 text-primary border">
                                                    <div class="flex items-start">
                                                        <div class="input-group rounded border-2">
                                                            <select @change="updateLine( index )" v-model="product.procurement.tax_group_id" class="p-2">
                                                                <option v-for="option of taxes" :key="option.id" :value="option.id">{{ option.name }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td :key="key" v-if="column.type === 'custom_select'" class="p-2 text-primary border">
                                                    <div class="flex items-start">
                                                        <div class="input-group rounded border-2">
                                                            <select @change="updateLine( index )" v-model="product.procurement[ key ]" class="p-2">
                                                                <option v-for="option of column.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td :key="key" v-if="column.type === 'currency'" class="p-2 text-primary border">
                                                    <div class="flex items-start flex-col justify-end">
                                                        <span class="text-sm text-primary">{{ product.procurement[ key ] | currency }}</span>
                                                    </div>
                                                </td>
                                                <td :key="key" v-if="column.type === 'unit_quantities'" class="p-2 text-primary border">
                                                    <div class="flex items-start">
                                                        <div class="input-group rounded border-2">
                                                            <select @change="fetchLastPurchasePrice( index )" v-model="product.procurement.unit_id" class="p-2 w-32">
                                                                <option v-for="option of product.unit_quantities" :key="option.id" :value="option.unit.id">{{ option.unit.name }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </td>
                                            </template>
                                        </tr>
                                        <tr class="text-primary">
                                            <td class="p-2 border" :colspan="Object.keys( form.columns ).indexOf( 'tax_value' )"></td>
                                            <td class="p-2 border">{{ totalTaxValues | currency }}</td>
                                            <td class="p-2 border" :colspan="Object.keys( form.columns ).indexOf( 'total_purchase_price' ) - ( Object.keys( form.columns ).indexOf( 'tax_value' ) + 1 )"></td>
                                            <td class="p-2 border">{{ totalPurchasePrice | currency }}</td>
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