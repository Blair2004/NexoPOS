<template>
    <div class="w-95vw flex flex-col h-95vh shadow-lg md:w-3/5-screen lg:w-2/5-screen md:h-3/5-screen bg-white">
        <div class="header bg-white border-b flex justify-between p-2 items-center">
            <h3>{{ __( 'Product / Service' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 flex-auto overflow-y-auto">
            <div class="h-full w-full flex justify-center items-center" v-if="! loaded">
                <ns-spinner></ns-spinner>
            </div>
            <template v-if="loaded">
                <ns-field v-for="(field, key) of fields" :key="key" :field="field"></ns-field>
            </template>
        </div>
        <div class="border-t flex justify-between p-2">
            <div></div>
            <div>
                <ns-button @click="addProduct()" type="info">{{ __( 'Create' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang';
import FormValidation from '@/libraries/form-validation';
import popupResolver from '@/libraries/popup-resolver';
import popupCloser from '@/libraries/popup-closer';
import { forkJoin } from 'rxjs';
import { nsSnackBar } from '@/bootstrap';
export default {
    name: 'ns-pos-quick-product-popup',
    methods: {
        __,
        popupCloser,
        popupResolver,

        close() {
            this.popupResolver( false );
        },

        async addProduct() {
            const valid    =   this.validation.validateFields( this.fields );

            if ( ! valid ) {
                return nsSnackBar.error( __( 'Unable to proceed. The form is not valid.' ) ).subscribe();
            }

            const product       =   this.validation.extractFields( this.fields );
            const quantities    =   {
                unit: this.units.filter( unit => unit.id === product.unit_id )[0],
                
                incl_tax_sale_price: product.unit_price,
                excl_tax_sale_price: 0,
                sale_price: product.unit_price,
                sale_price_tax: 0,
                sale_price_edit: 0,

                wholesale_price_tax: 0,
                incl_tax_wholesale_price: 0,
                excl_tax_wholesale_price: 0,
                wholesale_price: 0,
                wholesale_price_edit: 0,

                custom_price_tax : 0,
                custom_price : 0,
                custom_price_edit: 0,
                incl_tax_custom_price: 0,
                excl_tax_custom_price: 0
            };

            let tax_group;

            /**
             * this will get the taxes
             * and compute it for the product
             */
            try {
                await new Promise ( ( resolve, reject ) => {
                    if( product.tax_group_id ) {
                        nsHttpClient.get( `/api/nexopos/v4/taxes/groups/${product.tax_group_id}` )
                            .subscribe( taxGroup => {
                                quantities.sale_price_tax  =   taxGroup.taxes.map( tax => {
                                    return POS.getVatValue( quantities.sale_price, tax.rate, product.tax_type );
                                }).reduce( ( b, a ) => b + a );
    
                                quantities.wholesale_price_tax  =   taxGroup.taxes.map( tax => {
                                    return POS.getVatValue( quantities.wholesale_price, tax.rate, product.tax_type );
                                }).reduce( ( b, a ) => b + a );
    
                                quantities.excl_tax_sale_price  +=  quantities.sale_price_tax;
                                
                                tax_group            =   taxGroup;
    
                                resolve( true );
                            }, error => {
                                reject( false );
                            })
                    } else {
                        quantities.sale_price_tax       =   0;
                        quantities.wholesale_price_tax  =   0;
                        quantities.excl_tax_sale_price  =  product.unit_price;
                        
                        resolve( true );
                    }
                });

            } catch( exception ) {
                return nsSnackBar.error( __( 'An error has occured while computing the product.' ) ).subscribe();
            }
            
            product.$quantities     =   () => quantities;

            product.$original   =   () => {
                return {
                    stock_management: 'disabled',
                    category_id: 0,
                    tax_group,
                    tax_type: product.tax_type
                }
            }

            product.unit_name   =   this.units.filter( unit => unit.id === product.unit_id )[0].name;
            product.quantity    =   parseFloat( product.quantity );
            
            POS.addToCart( product );

            this.close();
        },

        loadData() {
            this.loaded     =   false;

            forkJoin(
                nsHttpClient.get( `/api/nexopos/v4/units` ),
                nsHttpClient.get( `/api/nexopos/v4/taxes/groups` ),
            ).subscribe({
                next: ( result ) => {
                    // ..
                    this.units          =   result[0];
                    this.tax_groups     =   result[1];

                    this.fields.filter( field => {
                        if ( field.name === 'tax_group_id' ) {
                            field.options   =   result[1].map( group => {
                                return {
                                    label: group.name,
                                    value: group.id,
                                }
                            })
                        }

                        if ( field.name === 'unit_id' ) {
                            field.options   =   result[0].map( unit => {
                                return {
                                    label: unit.name,
                                    value: unit.id,
                                }
                            })
                        }
                    });

                    this.buildForm();
                },
                error: ( error ) => {
                    // ..
                }
            })
        },

        buildForm() {
            this.fields     =   this.validation.createFields( this.fields );
            this.loaded     =   true;
        }
    },
    data() {
        return {
            units: [],
            tax_groups: [],
            loaded: false,
            validation: new FormValidation,
            fields: [
                {
                    label: __( 'Name' ),
                    name: 'name',
                    type: 'text',
                    description: __( 'Provide a unique name for the product.' ),
                    validation: 'required',
                }, {
                    label: __( 'Unit Price' ),
                    name: 'unit_price',
                    type: 'text',
                    description: __( 'Define what is the sale price of the item.' ),
                    validation: 'required',
                }, {
                    label: __( 'Quantity' ),
                    name: 'quantity',
                    type: 'text',
                    value: 1,
                    description: __( 'Set the quantity of the product.' ),
                    validation: 'required'
                }, {
                    label: __( 'Unit' ),
                    name: 'unit_id',
                    type: 'select',
                    options: [
                        // ...
                    ],
                    description: __( 'Assign a unit to the product.' ),
                    validation: 'required',                    
                }, {
                    label: __( 'Tax Type' ),
                    name: 'tax_type',
                    type: 'select',
                    options: [
                        {
                            label: __( 'Disabled' ),
                            value: '',
                        }, {
                            label: __( 'Inclusive' ),
                            value: 'inclusive',
                        }, {
                            label: __( 'Exclusive' ),
                            value: 'exclusive'
                        }
                    ],
                    description: __( 'Define what is tax type of the item.' ),               
                }, {
                    label: __( 'Tax Group' ),
                    name: 'tax_group_id',
                    type: 'select',
                    options: [
                        // ...
                    ],
                    description: __( 'Choose the tax group that should apply to the item.' ),                   
                }
            ]
        }
    },
    mounted() {
        this.popupCloser();
        this.loadData();
    }
}
</script>