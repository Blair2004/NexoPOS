<template>
    <div class="flex flex-col px-4 w-full">
        <div class="md:-mx-4 flex flex-col md:flex-row">
            <div class="md:px-4 w-full">
                <div class="input-group border-2 rounded info flex w-full">
                    <input :placeholder="__( 'Search products...' )" v-model="searchValue" type="text" class="flex-auto p-2 outline-none">
                    <button @click="setSalePrice()" class="px-2">{{ __( 'Set Sale Price' ) }}</button>
                </div>
                <div class="h-0 relative" v-if="results.length > 0 && searchValue.length > 0">
                    <ul class="ns-vertical-menu absolute w-full">
                        <li v-for="result of results" :key="result.id" @click="addResult( result )" class="p-2 border-b cursor-pointer">{{ result.name }}</li>
                    </ul>
                </div>
                <div class="my-2">
                    <table class="ns-table">
                        <thead>
                            <tr>
                                <th colspan="2" class="border">{{ __( 'Products' ) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(product,index) of products" :key="index">
                                <td colspan="2" class="border p-2">
                                    <div class="flex justify-between">
                                        <h3 class="font-bold">{{ product.name }}</h3>
                                        <span @click="removeProduct( index )" class="hover:underline text-error-secondary cursor-pointer">{{ __( 'Remove' ) }}</span>
                                    </div>
                                    <ul>
                                        <li @click="toggleUnitField( product )" class="flex justify-between p-1 hover:bg-box-elevation-hover">
                                            <span>{{ __( 'Unit' ) }}:</span> 
                                            <div class="input-group">
                                                <select @change="redefineUnit( product )" ref="unitField" type="text" v-model="product.unit_quantity_id">
                                                    <option :key="unitQuantity.id" :value="unitQuantity.id" v-for="unitQuantity of product.unit_quantities">{{ unitQuantity.unit.name }} ({{ unitQuantity.quantity }})</option>
                                                </select>
                                            </div>
                                        </li>
                                        <li @click="toggleQuantityField( product )" class="flex justify-between p-1 hover:bg-box-elevation-hover">
                                            <span>{{ __( 'Quantity' ) }}:</span>
                                            <span v-if="! product._quantity_toggled" class="cursor-pointer border-b border-dashed border-info-secondary">{{ product.quantity }}</span>
                                            <input ref="quantityField" type="text" v-model="product.quantity" v-if="product._quantity_toggled">
                                        </li>
                                        <li @click="togglePriceField( product )" class="flex justify-between p-1 hover:bg-box-elevation-hover">
                                            <span>{{ __( 'Price' ) }}:</span> 
                                            <span v-if="! product._price_toggled" class="cursor-pointer border-b border-dashed border-info-secondary">{{ nsCurrency( product.sale_price ) }}</span>
                                            <input ref="priceField" type="text" v-model="product.sale_price" v-if="product._price_toggled">
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td colspan="2" class="border p-2 text-center">
                                    {{ __( 'No product are added to this group.' ) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="products.length > 0">
                            <tr>
                                <td class="w-1/2 border p-2 text-left">{{ __( 'Total' ) }}</td>
                                <td class="w-1/2 border p-2 text-right">{{ nsCurrency( totalProducts ) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { Popup } from '~/libraries/popup';
import nsSelectPopupVue from '~/popups/ns-select-popup.vue';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'ns-product-group',
    props: [ 'fields' ],
    watch:{
        searchValue() {
            clearTimeout( this.searchTimeout );

            this.searchTimeout  =   setTimeout( () => {
                this.searchProducts( this.searchValue );
            }, 1000 );
        },
        products: {
            deep: true,
            handler() {
                this.$forceUpdate();
            }
        }
    },
    computed: {
        totalProducts() {
            if ( this.products.length > 0 ) {
                this.$emit( 'update', this.products );

                return this.products.map( product => {
                        return parseFloat( product.sale_price ) * parseFloat( product.quantity );
                    })
                    .reduce( ( a, b ) => a + b );
            }

            return 0;
        }
    },
    mounted() {
        const field  =   this.fields.filter( field => field.name === 'product_subitems' );

        if ( field.length > 0 && field[0].value !== undefined && field[0].value.length > 0) {
            this.products   =   field[0].value;
        }
    },
    data() {
        return {
            searchValue: '',
            searchTimeout: null,
            results: [],
            products: [],
        }
    },
    methods: {
        __,
        nsCurrency,
        setSalePrice() {
            this.$emit( 'updateSalePrice', this.totalProducts );
        },
        removeProduct( index ) {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Delete Sub item' ),
                message: __( 'Would you like to delete this sub item?' ),
                onAction: action => {
                    if ( action ) {
                        this.products.splice( index, 1 );
                    }
                }
            });
        },
        toggleUnitField( product ) {
            if ( ! product._unit_toggled ) {
                product._unit_toggled   =   ! product._unit_toggled;
            }

            setTimeout( () => {
                if ( product._unit_toggled ) {
                    this.$refs.unitField[0].addEventListener( 'blur', () => {
                        product._unit_toggled = false;
                        this.$forceUpdate();
                    });
                }
            }, 200 );
        },
        toggleQuantityField( product ) {
            product._quantity_toggled   =   ! product._quantity_toggled;

            setTimeout( () => {
                if ( product._quantity_toggled ) {
                    this.$refs.quantityField[0].select();
                    this.$refs.quantityField[0].addEventListener( 'blur', () => {
                        this.toggleQuantityField( product );
                        this.$forceUpdate();
                    });
                }
            }, 200 );
        },
        togglePriceField( product ) {
            product._price_toggled   =   ! product._price_toggled;

            setTimeout( () => {
                if ( product._price_toggled ) {
                    this.$refs.priceField[0].select();
                    this.$refs.priceField[0].addEventListener( 'blur', () => {
                        this.togglePriceField( product );
                        this.$forceUpdate();
                    });
                }
            }, 200 );
        },
        redefineUnit( product ) {
            const unitQuantity          =   product.unit_quantities.filter( unitQuantity => unitQuantity.id === product.unit_quantity_id );

            if ( unitQuantity.length > 0 ) {
                product.unit_quantity       =   unitQuantity[0];
                product.unit_id             =   unitQuantity[0].unit.id;
                product.unit                =   unitQuantity[0].unit;
                product.sale_price          =   unitQuantity[0].sale_price;
            }
        },

        async addResult( result ) {
            this.searchValue    =   '';

            if ( result.type === 'grouped' ) {
                return nsSnackBar
                    .error( __( 'Unable to add a grouped product.' ) )
                    .subscribe();
            }

            try {
                const selection    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, {
                        label: __( 'Choose The Unit' ),
                        options: result.unit_quantities.map( unitQuantity => {
                            return {
                                label: unitQuantity.unit.name,
                                value: unitQuantity.id
                            }
                        }),
                        resolve,
                        reject
                    })
                });

                const unitQuantity  =   result.unit_quantities
                    .filter( unitQuantity => parseInt( unitQuantity.id ) === parseInt( selection[0].value ) );

                /**
                 * Adding product to the Array
                 */
                this.products.push({
                    name: result.name,
                    unit_quantity_id: selection[0].value,
                    unit_quantity: unitQuantity[0],
                    unit_id: unitQuantity[0].unit.id,
                    unit: unitQuantity[0].unit,
                    product_id: unitQuantity[0].product_id,
                    quantity: 1,
                    _price_toggled: false,
                    _quantity_toggled: false,
                    _unit_toggled: false,
                    unit_quantities: result.unit_quantities,
                    sale_price: unitQuantity[0].sale_price
                });

                this.$emit( 'update', this.products );

            } catch( exception ) {
                console.log( exception );
            }
        },
        searchProducts( search ) {
            if ( search.length === 0 ) {
                return null;
            }
            
            nsHttpClient.post( `/api/products/search`, { 
                search, 
                arguments: {
                    type: {
                        comparison: '<>',
                        value: 'grouped'
                    },
                    searchable: {
                        comparison: 'in',
                        value: [ 0, 1 ]
                    }
                }
            }).subscribe({
                next: results => {
                    this.results    =   results;
                },
                error: error => {
                    nsSnackBar.error( error.message || __( 'An unexpected error occurred' ), __( 'Ok' ), { duration: 3000 }).subscribe();
                }
            })
        }
    }
}
</script>
