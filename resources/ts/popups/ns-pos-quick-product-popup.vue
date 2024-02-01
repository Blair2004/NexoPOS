<template>
    <div class="w-95vw flex flex-col h-95vh shadow-lg md:w-3/5-screen lg:w-2/5-screen md:h-3/5-screen ns-box">
        <div class="header ns-box-header border-b flex justify-between p-2 items-center">
            <h3>{{ __( 'Product / Service' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="ns-box-body p-2 flex-auto overflow-y-auto">
            <div class="h-full w-full flex justify-center items-center" v-if="! loaded">
                <ns-spinner></ns-spinner>
            </div>
            <template v-if="loaded">
                <template v-for="( field, key ) of fields">
                    <ns-field v-if="( field.show && field.show( form ) ) || ! field.show" :key="key" :field="field"></ns-field>
                </template>
            </template>
        </div>
        <div class="ns-box-footer border-t flex justify-between p-2">
            <div></div>
            <div>
                <ns-button @click="addProduct()" type="info">{{ __( 'Create' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import FormValidation from '~/libraries/form-validation';
import popupResolver from '~/libraries/popup-resolver';
import popupCloser from '~/libraries/popup-closer';
import { forkJoin } from 'rxjs';
import { nsSnackBar } from '~/bootstrap';

declare const POS;

export default {
    name: 'ns-pos-quick-product-popup',
    props: [ 'popup' ],
    methods: {
        __,
        popupCloser,
        popupResolver,

        close() {
            this.popupResolver( false );
        },

        async addProduct() {
            
            const extractedFields   =   this.validation.extractFields( this.fields );
            const fields =   this.fields.filter( field => typeof field.show === 'undefined' || ( typeof field.show === 'function' && field.show( extractedFields ) ) );
            const valid =   this.validation.validateFields( fields );

            if ( ! valid ) {
                return nsSnackBar.error( __( 'Unable to proceed. The form is not valid.' ) ).subscribe();
            }

            let product       =   this.validation.extractFields( fields );
            
            product.$original   =   () => {
                return {
                    stock_management: 'disabled',
                    category_id: 0,
                    tax_group: this.tax_groups.filter( taxGroup => parseInt( taxGroup.id ) === parseInt( product.tax_group_id ) )[0],
                    tax_group_id: product.tax_group_id,
                    tax_type: product.tax_type
                }
            }

            if ( product.product_type === 'product' ) {
                product.unit_name           =   this.units.filter( unit => unit.id === product.unit_id )[0].name;
                product.quantity            =   parseFloat( product.quantity );
                product.unit_price          =   parseFloat( product.unit_price );
                product.mode                =   'custom';
                product.price_with_tax      =   product.unit_price;
                product.price_without_tax   =   product.unit_price;
                product.tax_value           =   0;
            } else {
                product.unit_name   =   __( 'N/A' );
                product.unit_price  =   0;
                product.quantity    =   1; // it's always 1
            }

            const quantities        =   await POS.defineQuantities( product, this.units );
            product.$quantities     =   () => quantities;

            /**
             * we initially need to compute the product
             * tax before adding that to the cart.
             */
            product     =   POS.computeProductTax( product );
            
            POS.addToCart( product );

            this.close();
        },

        loadData() {
            this.loaded     =   false;

            forkJoin(
                nsHttpClient.get( `/api/units` ),
                nsHttpClient.get( `/api/taxes/groups` ),
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
                            });

                            // if we have at least one tax group, this latest is selected by default.
                            if ( result[1].length > 0 && result[1][0].id !== undefined ) {
                                field.value = result[1][0].id || this.options.ns_pos_tax_group;
                            }
                        }

                        if ( field.name === 'tax_type' ) {
                            field.value = this.options.tax_type || 'inclusive';
                        }

                        if ( field.name === 'unit_id' ) {
                            field.value     =   this.options.ns_pos_quick_product_default_unit;
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

            setTimeout(() => {
                this.$el.querySelector( '#name' ).select();
            }, 100);
        }
    },
    computed: {
        form() {
            return this.validation.extractFields( this.fields );
        }
    },
    data() {
        return {
            units: [],
            options: POS.options.getValue(),
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
                    label: __( 'Product Type' ),
                    name: 'product_type',
                    type: 'select',
                    description: __( 'Define the product type.' ),
                    options: [{
                        label: __( 'Normal' ),
                        value: 'product',
                    }, {
                        label: __( 'Dynamic' ),
                        value: 'dynamic',
                    }],
                    value: 'product',
                    validation: 'required',
                }, {
                    label: __( 'Rate' ),
                    name: 'rate',
                    type: 'text',
                    description: __( 'In case the product is computed based on a percentage, define the rate here.' ),
                    validation: 'required',
                    show( form ) {
                        return form.product_type === 'dynamic';
                    }
                }, {
                    label: __( 'Unit Price' ),
                    name: 'unit_price',
                    type: 'text',
                    description: __( 'Define what is the sale price of the item.' ),
                    validation: '',
                    value: 0,
                    show( form ) {
                        return form.product_type === 'product';
                    }
                }, {
                    label: __( 'Quantity' ),
                    name: 'quantity',
                    type: 'text',
                    value: 1,
                    description: __( 'Set the quantity of the product.' ),
                    validation: '',
                    show( form ) {
                        return form.product_type === 'product';
                    }
                }, {
                    label: __( 'Unit' ),
                    name: 'unit_id',
                    type: 'select',
                    options: [
                        // ...
                    ],
                    description: __( 'Assign a unit to the product.' ),
                    validation: '',  
                    show( form ) {
                        return form.product_type === 'product';
                    }                  
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
                    show( form ) {
                        return form.product_type === 'product';
                    }             
                }, {
                    label: __( 'Tax Group' ),
                    name: 'tax_group_id',
                    type: 'select',
                    options: [
                        // ...
                    ],
                    description: __( 'Choose the tax group that should apply to the item.' ),  
                    show( form ) {
                        return form.product_type === 'product';
                    }                 
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