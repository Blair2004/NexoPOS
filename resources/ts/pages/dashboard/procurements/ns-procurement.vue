<script lang="ts">
import FormValidation from '~/libraries/form-validation';
import { BehaviorSubject, forkJoin } from "rxjs";
import { nsSnackBar, nsHttpClient, nsNotice } from '~/bootstrap';
import nsManageProducts from '~/pages/dashboard/procurements/manage-products.vue';
import Tax from "~/libraries/tax";
import nsProcurementProductOptionsVue from '~/popups/ns-procurement-product-options.vue';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';
import { Popup } from '~/libraries/popup';
import NsNumpadPopup from '~/popups/ns-numpad-popup.vue';
import NsSelectPopup from '~/popups/ns-select-popup.vue';
import { selectApiEntities } from '~/libraries/select-api-entities';
import { Unit } from '~/interfaces/unit';
import { nsPOSLoadingPopup } from '~/components/components';


export default {
    name: 'ns-procurement',
    mounted() {
        this.reloadEntities();

        this.shouldPreventAccidentlRefreshSubscriber    =   this.shouldPreventAccidentalRefresh.subscribe({ 
            next: value => {
                if ( value ){
                    window.addEventListener( 'beforeunload', this.addAccidentalCloseListener );
                } else {
                    window.removeEventListener( 'beforeunload', this.addAccidentalCloseListener );
                }
            }
        })
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
            form: <any>{},

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

            /**
             * determine if we should bypass the accidental
             * load of the page when products are added
             */
            shouldPreventAccidentalRefresh: new BehaviorSubject( false ),
            shouldPreventAccidentlRefreshSubscriber: null,

            /**
             * Determine if we should show the info box
             */
            showInfo: false,
        }
    },
    watch: {
        form: {
            handler() {
                if( this.formValidation.isFormUntouched( this.form ) ) {
                    this.shouldPreventAccidentalRefresh.next(false);
                } else {
                    this.shouldPreventAccidentalRefresh.next(true);
                }
            },
            deep: true
        },
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
        nsManageProducts
    },
    props: [ 'submitMethod', 'submitUrl', 'returnUrl', 'src', 'rules' ],
    methods: {
        __,
        nsCurrency,

        addAccidentalCloseListener( event ) {
            event.preventDefault();
            return true;
        },

        async defineConversionOption( index ) {
            try {
                const product   =   this.form.products[ index ];

                if ( product.procurement.unit_id === undefined ) {
                    return nsNotice.error( 
                        __( 'An error has occured' ),
                        __( 'Select the procured unit first before selecting the conversion unit.' ), {
                            actions: {
                                learnMore: {
                                    label: __( 'Learn More' ),
                                    onClick: ( instance ) => {
                                        console.log( instance )
                                    }
                                },
                                close: {
                                    label: __( 'Close' ),
                                    onClick: ( instance ) => {
                                        instance.close();
                                    }
                                }
                            },
                            duration: 5000
                        }
                    )
                }

                const result    =   await selectApiEntities( 
                    `/api/units/${product.procurement.unit_id}/siblings`, 
                    __( 'Convert to unit' ), 
                    product.procurement.convert_unit_id || null, 
                    "select"
                );

                product.procurement.convert_unit_id     =   result.values[0];
                product.procurement.convert_unit_label  =   result.labels[0];
            } catch( exception ) {
                if ( exception !== false ) {
                    return nsSnackBar
                        .error( exception.message || __( 'An unexpected error has occured' ) )
                        .subscribe();
                }
            }
        },
        
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
            nsHttpClient.post( '/api/procurements/products/search-product', { search })
                .subscribe( (result: any[]) => {
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
                nsHttpClient.get( '/api/categories' ),
                nsHttpClient.get( '/api/products' ),
                nsHttpClient.get( this.src ),
                nsHttpClient.get( '/api/taxes/groups' ),
            ]).subscribe( (result: any[]) => {
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
            product.procurement.tax_group_id                =   product.tax_group_id;
            product.procurement.tax_type                    =   product.tax_type || 'inclusive';
            product.procurement.unit_id                     =   product.unit_quantities[0].unit_id;
            product.procurement.product_id                  =   product.id;
            product.procurement.convert_unit_id             =   product.unit_quantities[0].convert_unit_id;
            product.procurement.procurement_id              =   null;
            product.procurement.$invalid                    =   false;

            this.searchResult           =   [];
            this.searchValue            =   '';

            this.form.products.push( product );

            const indexOfProduct = this.form.products.length - 1;
            
            this.fetchLastPurchasePrice( indexOfProduct );
        },
        submit() {

            if ( this.form.products.length === 0 ) {
                return nsSnackBar.error( __( 'Unable to proceed, no product were provided.' ), __( 'OK' ) )
                    .subscribe();
            }

            this.form.products.forEach( (product: any) => {
                if ( ! (parseFloat( product.procurement.quantity ) >= 1) ) {
                    product.procurement.$invalid    =   true;
                } else if ( product.procurement.unit_id === 0 ) {
                    product.procurement.$invalid    =   true;
                } else {
                    product.procurement.$invalid    =   false;
                }
            });

            const invalidProducts   =   this.form.products.filter( product => product.procurement.$invalid );

            if ( invalidProducts.length > 0 ) {
                return nsSnackBar.error( __( 'Unable to proceed, one or more product has incorrect values.' ), __( 'OK' ) )
                    .subscribe();
            }

            if ( this.formValidation.validateForm( this.form ).length > 0 ) {
                /**
                 * hack to force rerendering
                 * there might be a better solutin here.
                 */
                this.setTabActive( this.activeTab );

                return nsSnackBar.error( __( 'Unable to proceed, the procurement form is not valid.' ), __( 'OK' ) )
                    .subscribe();
            }

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( __( 'Unable to submit, no valid submit URL were provided.' ), __( 'OK' ) )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            const data  =   {
                ...this.formValidation.extractForm( this.form ), ...{
                    products: this.form.products.map( product => product.procurement )
                }
            }

            const popup = Popup.show( nsPOSLoadingPopup );

            nsHttpClient[ this.submitMethod ? this.submitMethod.toLowerCase() : 'post' ]( this.submitUrl, data )
                .subscribe({
                    next: data => {                        
                        if ( data.status === 'success' ) {
                            this.shouldPreventAccidentalRefresh.next(false);
                            return document.location   =   this.returnUrl;
                        }

                        popup.close();
                        this.formValidation.enableForm( this.form );
                    }, 
                    error: ( error ) => {
                        popup.close();

                        nsSnackBar.error( error.message, undefined, {
                            duration: 5000
                        }).subscribe();

                        this.formValidation.enableForm( this.form );
                        
                        if ( error.errors ) {
                            this.formValidation.triggerError( this.form, error.errors );
                        }
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
            
            promise.then( (value: { [ key:string ] : any }) => {
                for( let key in value ) {
                    this.form.products[ index ].procurement[ key ]      =   value[ key ];
                }

                this.updateLine( index );
            });
        },

        async selectUnitForProduct( index ) {
            try {
                const product   =   this.form.products[ index ];
                const unitID    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( NsSelectPopup, {
                        label: __( '{product}: Purchase Unit' ).replace( '{product}', product.name ),
                        description: __( 'The product will be procured on that unit.' ),
                        value: product.unit_id,
                        resolve,
                        reject,
                        options: product.unit_quantities.map( unitQuantity => {
                            return {
                                label: unitQuantity.unit.name,
                                value: unitQuantity.unit.id
                            }
                        })
                    })
                })

                product.procurement.unit_id     =   unitID;

                /**
                 * every modification here must reset the conversion
                 * unit. This will avoid having the conversion unit be the same
                 * as the procured unit.
                 */
                const selectedUnitQuantity  =   product.unit_quantities.filter( unitQuantity => parseInt( unitQuantity.unit_id ) === +unitID );
                
                product.procurement.convert_unit_id         =   selectedUnitQuantity[0].convert_unit_id || undefined;
                product.procurement.convert_unit_label      =   await new Promise( ( resolve, reject ) => {
                    if ( product.procurement.convert_unit_id !== undefined ) {
                        nsHttpClient.get( `/api/units/${product.procurement.convert_unit_id}` )
                            .subscribe({
                                next: ( result: Unit ) => {
                                    resolve( result.name );
                                },
                                error: result => {
                                    resolve( __( 'Unkown Unit' ) );
                                }
                            })
                    } else {
                        resolve( __( 'N/A' ) );
                    }
                });

                this.fetchLastPurchasePrice( index );
            } catch( exception ) {
                console.log( exception );
            }
        },

        async selectTax( index ) {
            try {
                const product   =   this.form.products[ index ];
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( NsSelectPopup, {
                        label: __( 'Choose Tax' ),
                        description: __( 'The tax will be assigned to the procured product.' ),
                        resolve,
                        reject,
                        options: this.taxes.map( tax => {
                            return {
                                label: tax.name,
                                value: tax.id
                            }
                        })
                    })
                })

                product.procurement.tax_group_id     =   result;
                
                this.updateLine( index );

            } catch( exception ) {

            }
        },

        async triggerKeyboard( entry, key, index ) {
            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( NsNumpadPopup, {
                        value: entry[ key ],
                        resolve, 
                        reject
                    });
                });

                entry[ key ]    =   result;

                this.updateLine( index );
            } catch ( exception ) {
                console.log({ exception })
            }
        },

        getSelectedTax( index ) {
            const product   =   this.form.products[ index ];
            const select    =   this.taxes.filter( tax => {
                if ( product.procurement.tax_group_id && product.procurement.tax_group_id === tax.id ) {
                    return true;
                }
                return false;
            });

            if ( select.length === 1 ) {
                return select[0].name;
            }

            return __( 'N/A' );
        },

        getSelectedUnit( index ) {
            const product   =   this.form.products[ index ];
            const units     =   product.unit_quantities.map( unitQuantity => unitQuantity.unit );
            const select    =   units.filter( unit => {
                if ( product.procurement.unit_id !== undefined ) {
                    return unit.id === product.procurement.unit_id
                }
                return false;
            });

            if ( select.length === 1 ) {
                return select[0].name;
            }

            return __( 'N/A' );
        },

        handleSavedEvent( event, field ) {
            if ( event.data ) {
                field.options.push({
                    label: event.data.entry.first_name,
                    value: event.data.entry.id
                });

                field.value     =   event.data.entry.id;
            }
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
                    <div for="title" class="text-sm my-2 -mx-1 flex text-primary">
                        <div class="px-1" @click="showInfo = !showInfo">
                            <span v-if="!showInfo" class="cursor-pointer rounded-full ns-inset-button border px-2 py-1">{{ __( 'Show Details' ) }}</span>
                            <span v-if="showInfo" class="cursor-pointer rounded-full ns-inset-button border px-2 py-1">{{ __( 'Hide Details' ) }}</span>
                        </div>
                        <div class="px-1">
                            <a v-if="returnUrl" :href="returnUrl" class="rounded-full ns-inset-button border px-2 py-1">{{ __( 'Go Back' ) }}</a>
                        </div>
                    </div>
                </div>
                <div :class="form.main.disabled ? 'disabled' : ( form.main.errors.length > 0 ? 'error' : '' )" class="flex border-2 rounded input-group info overflow-hidden">
                    <input v-model="form.main.value" 
                        @keypress="formValidation.checkField( form.main )"
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
            <div v-if="showInfo" class="rounded border-2 bg-info-primary border-info-tertiary flex">
                <div class="icon w-16 flex py-4 justify-center">
                    <i class="las la-info-circle text-4xl"></i>
                </div>
                <div class="text flex-auto py-4">
                    <h3 class="font-bold text-lg">{{ __( 'Important Notes' ) }}</h3>
                    <ul>
                        <li>
                            <i class="las la-hand-point-right">&nbsp;</i>
                            <span>{{ __( 'Stock Management Products.' ) }}</span>
                        </li>
                        <li>
                            <i class="las la-hand-point-right">&nbsp;</i>
                            <span>{{ __( 'Doesn\'t work with Grouped Product.' ) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div id="form-container" class="-mx-4 flex flex-wrap mt-4">
                <div class="px-4 w-full">
                    <div id="tabbed-card" class="ns-tab">
                        <div id="card-header" class="flex flex-wrap">
                            <div @click="setTabActive( tab )" :class="tab.active ? 'active' : 'inactive'" v-for="( tab, index ) of validTabs" v-bind:key="index" class="tab cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg text-primary">
                                {{ tab.label }}
                            </div>
                        </div>
                        <div class="ns-tab-item" v-if="activeTab.identifier === 'details'">
                            <div class="card-body rounded-br-lg rounded-bl-lg shadow p-2">
                                <div class="-mx-4 flex flex-wrap" v-if="form.tabs">
                                    <div class="flex px-4 w-full md:w-1/2 lg:w-1/3" :key="index" v-for="(field, index) of form.tabs.general.fields">
                                        <ns-field @saved="handleSavedEvent( $event, field )" :field="field"></ns-field>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ns-tab-item" v-if="activeTab.identifier === 'products'">
                            <div class="card-body rounded-br-lg rounded-bl-lg shadow p-2">

                                <div class="mb-2">
                                    <div class="input-group info flex border-2 rounded overflow-hidden">
                                        <input
                                            v-model="searchValue"
                                            type="text" 
                                            :placeholder="__( 'SKU, Barcode, Name' )"
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
                                                <template v-for="( column, key ) of form.columns">                                                                                                       
                                                    <td :key="key" v-if="column.type === 'name'" width="500" class="p-2 text-primary border">
                                                        <span class="">{{ product.name }}</span>
                                                        <div class="flex">
                                                            <div class="flex md:flex-row flex-col md:-mx-1">
                                                                <div class="md:px-1">
                                                                    <span class="text-xs text-info-tertiary cursor-pointer underline" @click="deleteProduct( index )">{{ __( 'Delete' ) }}</span>
                                                                </div>
                                                                <div class="md:px-1">
                                                                    <span class="text-xs text-info-tertiary cursor-pointer underline" @click="setProductOptions( index )">{{ __( 'Options' ) }}</span>
                                                                </div>
                                                                <div class="md:px-1">
                                                                    <span class="text-xs text-info-tertiary cursor-pointer underline" @click="selectUnitForProduct( index )">{{ __( 'Unit' ) }}: {{ getSelectedUnit( index ) }}</span>
                                                                </div>
                                                                <div class="md:px-1">
                                                                    <span class="text-xs text-info-tertiary cursor-pointer underline" @click="selectTax( index )">{{ __( 'Tax' ) }}: {{ getSelectedTax( index ) }}</span>
                                                                </div>
                                                                <div class="md:px-1">
                                                                    <span class="text-xs text-info-tertiary cursor-pointer underline" @click="defineConversionOption( index )">{{ __( 'Convert' ) }}: {{ product.procurement.convert_unit_id ? product.procurement.convert_unit_label : __( 'N/A' ) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td> 
                                                    <td :key="key" v-if="column.type === 'text'" @click="triggerKeyboard( product.procurement, key, index )" class="text-primary border cursor-pointer">
                                                        <div class="flex justify-center">
                                                            <span v-if="[ 'purchase_price_edit' ].includes( <any>key )" class="outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ nsCurrency(product.procurement[ key ]) }}</span>
                                                            <span v-if="! [ 'purchase_price_edit' ].includes( <any>key )" class="outline-none border-dashed py-1 border-b border-info-primary text-sm">{{ product.procurement[ key ] }}</span>
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
                                                            <span class="text-sm text-primary">{{ nsCurrency( product.procurement[ key ] ) }}</span>
                                                        </div>
                                                    </td>
                                                </template>
                                            </tr>
                                            <tr class="text-primary">
                                                <td class="p-2 border" :colspan="Object.keys( form.columns ).indexOf( 'tax_value' )"></td>
                                                <td class="p-2 border">{{ nsCurrency( totalTaxValues ) }}</td>
                                                <td class="p-2 border" :colspan="Object.keys( form.columns ).indexOf( 'total_purchase_price' ) - ( Object.keys( form.columns ).indexOf( 'tax_value' ) + 1 )"></td>
                                                <td class="p-2 border">{{ nsCurrency( totalPurchasePrice ) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>