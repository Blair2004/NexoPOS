<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { Popup } from '~/libraries/popup';
import nsProcurementQuantityVue from '~/popups/ns-procurement-quantity.vue';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import nsPromptPopupVue from '~/popups/ns-prompt-popup.vue';
import { __ } from '~/libraries/lang';
import { forkJoin } from 'rxjs';
import { nsCurrency } from '~/filters/currency';
import nsSelectPopupVue from '~/popups/ns-select-popup.vue';

export default {
    name: 'ns-stock-adjustment',
    props: [ 'actions' ],
    data() {
        return {
            search: '',
            timeout: null,
            suggestions: [],
            products: [],
        }
    },
    mounted() {
        // ...
    },
    methods: {
        __,
        nsCurrency,
        searchProduct( argument ) {
            if ( argument.length > 0 ) {
                nsHttpClient.post( '/api/procurements/products/search-procurement-product', { argument })
                    .subscribe( result => {
                        if ( result.from === 'products' ) {
                            if ( result.products.length > 0 ) {
                                if ( result.products.length === 1 ) {
                                    this.addSuggestion( result.products[0] );
                                } else {
                                    this.suggestions    =   result.products;
                                }
                            } else {
                                this.closeSearch();
                                return nsSnackBar.error( __( 'Looks like no valid products matched the searched term.' ) ).subscribe();
                            }
                        } else if ( result.from === 'procurements' ) {
                            if ( result.product === null ) {
                                this.closeSearch();
                                return nsSnackBar.error( __( 'Looks like no valid products matched the searched term.' ) ).subscribe();
                            } else {
                                this.addProductToList( result.product );
                            }
                        }
                    })
            }
        },

        addProductToList( product ) {
            const exists    =   this.products
                .filter( __product => __product.procurement_product_id === product.id );

            if ( exists.length > 0 ) {
                this.closeSearch();
                return nsSnackBar.error( __( 'The product already exists on the table.' ) ).subscribe();
            }

            const action = this.actions.filter( action => action.value === 'deleted' );
            let defaultAction = action.length === 1 ? action[0] : { value: 'deleted' };

            const finalProduct                  =   new Object;
            product.unit_quantity.unit          =   product.unit;
            finalProduct.selected               =   false;
            finalProduct.quantities             =   [ product.unit_quantity ];
            finalProduct.name                   =   product.name;
            finalProduct.adjust_unit            =   product.unit_quantity;

            finalProduct.adjust_quantity            =   1;
            finalProduct.adjust_action              =   defaultAction.value, // this is the default adjust_action
            finalProduct.adjust_reason              =   '',
            finalProduct.adjust_value               =   0;
            finalProduct.id                         =   product.product_id;
            finalProduct.accurate_tracking          =   1;
            finalProduct.available_quantity         =   product.available_quantity;
            finalProduct.procurement_product_id     =   product.id;
            finalProduct.procurement_history        =   [{
                label: `${product.procurement.name} (${product.available_quantity})`,
                value: product.id
            }]
 
            this.recalculateProduct( finalProduct );
            this.products.unshift( finalProduct );
            this.clearSearch();
        },

        addSuggestion( suggestion ) {
            const alreadyAdded    =   this.products
                .filter( product => product.id === suggestion.id ).length > 0;

            forkJoin([
                nsHttpClient.get( `/api/products/${suggestion.id}/units/quantities` ),
                // nsHttpClient.get( `/api/products/${suggestion.id}/procurements` )
            ]).subscribe( result => {
                    if ( result[0].length > 0 ) {

                        const defaultUnit = result[0].filter( unitQuantity => unitQuantity.unit.base_unit );

                        const action = this.actions.filter( action => action.value === 'set' );
                        let defaultAction = action.length === 1 ? action[0] : { value: 'set' };

                        suggestion.selected                         =   false;
                        suggestion.quantities                       =   result[0];
                        suggestion.adjust_quantity                  =   1;
                        suggestion.adjust_action                    =   defaultAction.value,
                        suggestion.adjust_reason                    =   '',
                        suggestion.adjust_unit                      =   defaultUnit.length > 0 && ! alreadyAdded ? defaultUnit[0]: '',
                        suggestion.adjust_value                     =   0;
                        suggestion.procurement_product_id           =   0;

                        this.recalculateProduct( suggestion );
                        this.products.unshift( suggestion );
                        this.clearSearch();
                    } else {
                        return nsSnackBar.error( __( `This product doesn't have any stock to adjust.` ) ).subscribe();
                    }

                    if ( suggestion.accurate_tracking === 1 ) {
                        // suggestion.procurement_history      =   result[1].map( product => {
                        //     return {
                        //         label: `${product.procurement.name} (${product.available_quantity})`,
                        //         value: product.id
                        //     }
                        // })
                    }
                });
        },
        closeSearch() {
            this.$refs.searchField.select();
            this.suggestions    =   [];
        },
        clearSearch() {
            this.search         =   '';
            this.suggestions    =   [];
        },
        recalculateProduct( product ) {
            if ( product.adjust_unit !== '' ) {
                if ([ 'deleted', 'defective', 'lost' ].includes( product.adjust_action ) ) {
                    product.adjust_value        =   - ( product.adjust_quantity * product.adjust_unit.sale_price );
                } else {
                    product.adjust_value        =   product.adjust_quantity * product.adjust_unit.sale_price;
                }
            }
            this.$forceUpdate();
        },
        openQuantityPopup( product ) {
            const oldQuantity   =   product.quantity;
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsProcurementQuantityVue, { resolve, reject, quantity : product.adjust_quantity });
            });

            promise.then( result => {
                /**
                 * will check the stock if the adjustment
                 * reduce the stock.
                 */
                if ( ! [ 'added', 'set' ].includes( product.adjust_action ) ) {
                    if ( product.accurate_tracking !== undefined && result.quantity > product.available_quantity ) {
                        return nsSnackBar.error( __( 'The specified quantity exceed the available quantity.' ) ).subscribe();
                    } else if ( result.quantity > product.adjust_unit.quantity ) {
                        return nsSnackBar.error( __( 'The specified quantity exceed the available quantity.' ) ).subscribe();
                    }
                }

                product.adjust_quantity     =   result.quantity;

                this.recalculateProduct( product );
            });
        },
        proceedStockAdjustment() {
            if ( this.products.length === 0 ) {
                return nsSnackBar.error( __( 'Unable to proceed as the table is empty.' ) ).subscribe();
            }

            Popup.show( nsPosConfirmPopupVue, { 
                title: __( 'Confirm Your Action' ),
                message: __( 'The stock adjustment is about to be made. Would you like to confirm ?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( '/api/products/adjustments', { products: this.products })
                            .subscribe( result => {
                                nsSnackBar.success( result.message ).subscribe();
                                this.products       =   [];
                            }, error => {
                                nsSnackBar.error( error.message ).subscribe();
                            });
                    }
                }
            });
        },
        provideReason( product ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsPromptPopupVue, {
                    title: __( 'More Details' ),
                    resolve,
                    reject,
                    message: __( 'Useful to describe better what are the reasons that leaded to this adjustment.' ),
                    input: product.adjust_reason,
                    onAction: ( input ) => {
                        if ( input !== false ) {
                            product.adjust_reason     =   input;
                        }
                    }
                });
            });

            promise.then( result => {
                nsSnackBar.success( __( 'The reason has been updated.' ) ).susbcribe();
            }).catch( error => {
                // ...
            })
        },
        async selectAdjustmentUnit( product ) {
            try {
                const result = await new Promise( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, {
                        label: __( 'Select Unit' ),
                        resolve,
                        reject,
                        description: __( 'Select the unit that you want to adjust the stock with.' ),
                        name: 'adjust_unit',
                        options: product.quantities.map( quantity => {
                            return {
                                label: quantity.unit.name,
                                value: quantity
                            }
                        }),
                    });
                });

                const index             =   this.products.indexOf( product );
                const otherProducts     =   this.products.filter( ( product, _index ) => _index !== index );
                const exists    =   otherProducts
                    .filter( __product => __product.adjust_unit.unit.id === result.unit.id && __product.adjust_unit.product_id === result.product_id ).length > 0;

                if ( exists ) {
                    return nsSnackBar.error( __( 'A similar product with the same unit already exists.' ) ).subscribe();
                }
                
                product.adjust_unit    =   result;
                this.recalculateProduct( product );

            } catch ( error ) {
                // ...
            }
        },
        async selectProcurement( product ) {
            try {
                console.log( product );
                const result = await new Promise( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, {
                        label: __( 'Select Procurement' ),
                        resolve,
                        reject,
                        description: __( 'Select the procurement that you want to adjust the stock with.' ),
                        name: 'adjust_procurement',
                        options: product.procurement_history,
                    });
                });

                product.procurement_product_id    =   result;
                
                this.recalculateProduct( product );
            } catch ( exception ) {
                throw exception;
            }
        },
        async selectStockAdjustementAction( product ) {
            try {
                const result = await new Promise( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, {
                        label: __( 'Select Action' ),
                        resolve,
                        reject,
                        description: __( 'Select the action that you want to perform on the stock.' ),
                        name: 'adjust_action',
                        options: this.actions,
                    });
                });

                product.adjust_action    =   result;
            } catch ( exception ) {
                throw exception;
            }
        },
        removeProduct( product ) {
            Popup.show( nsPosConfirmPopupVue, { 
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to remove this product from the table ?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        const index     =   this.products.indexOf( product );
                        this.products.splice( index, 1 );
                    }
                }
            });
        },
        getAdjustActionLabel( action ) {
            const filtredAction =  this.actions.filter( _action => _action.value === action );

            if ( filtredAction.length > 0 ) {
                return filtredAction[0].label;
            }

            return __( 'N/A' );
        },
        deleteSelectedProducts() {
            Popup.show( nsPosConfirmPopupVue, { 
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to remove the selected products from the table ?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.products   =   this.products.filter( product => ! product.selected );
                    }
                }
            });
        }
    },
    watch: {
        search() {
            if ( this.search.length > 0 ) {
                clearTimeout( this.timeout );
                this.timeout    =   setTimeout( () => {
                    this.searchProduct( this.search );
                }, 500 );
            } else {
                this.closeSearch();
            }
        }
    }
}
</script>
<template>
    <div>
        <div class="input-field flex border-2 input-group rounded">
            <input @keyup.esc="closeSearch()" ref="searchField" v-model="search" type="text" class="p-2 flex-auto outline-none">
            <button class="px-3 py-2 rounded-none">{{ __( 'Search' ) }}</button>
        </div>
        <div class="h-0" v-if="suggestions.length > 0">
            <div class="">
                <ul class="shadow h-96 relative z-10 ns-vertical-menu zoom-in-entrance anim-duration-300 overflow-y-auto">
                    <li @click="addSuggestion( suggestion )" v-for="suggestion of suggestions" :key="suggestion.id" class="cursor-pointer border-b p-2 flex justify-between">
                        <span>{{ suggestion.name }}</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="ns-box rounded shadow my-2 w-full ">
            <table class="table w-full ns-table">
                <thead class="border-b">
                    <tr>
                        <td class="p-2">{{ __( 'Product' ) }}</td>
                        <td width="120" class="p-2 text-center hidden md:table-cell">{{ __( 'Quantity' ) }}</td>
                        <td width="120" class="p-2 text-center hidden md:table-cell">{{ __( 'Value' ) }}</td>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="products.length === 0">
                        <td class="p-2 border-b text-center hidden md:table-cell" colspan="6">{{ __( 'Search and add some products' ) }}</td>
                        <td class="p-2 border-b text-center table-cell md:hidden" colspan="4">{{ __( 'Search and add some products' ) }}</td>
                    </tr>
                    <tr :key="product.id" v-for="product of products">
                        <td class="p-2 border">
                            <div class="flex justify-between">
                                <div>
                                    <h3 class="font-bold cursor-pointer" @click="product.selected  =   ! product.selected"><input type="checkbox" :checked="product.selected" name="" id=""> {{ product.name }} ({{ ( product.accurate_tracking === 1 ? product.available_quantity : product.adjust_unit.quantity ) || 0 }})</h3>
                                </div>
                                <div>
                                    <span class="md:hidden cursor-pointer border-dashed border-b border-info-secondary" @click="openQuantityPopup( product )">
                                        {{ __( 'Quantity' ) }} : {{ product.adjust_quantity }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex -mx-2 md:flex-row flex-wrap">
                                <div class="px-2 w-1/2 md:w-auto" @click="selectAdjustmentUnit( product )">
                                    <div class="text-xs cursor-pointer border-b border-dashed border-info-secondary py-1">
                                        <span class="text-xs">{{ __( 'Unit:' ) }}</span>&nbsp;
                                        <span v-if="product.adjust_unit.unit" class="">{{ product.adjust_unit.unit.name }}</span>
                                        <span v-if="! product.adjust_unit.unit">{{ __( 'N/A' ) }}</span>
                                    </div>
                                </div>
                                <div class="px-2 w-1/2 md:w-auto" @click="selectStockAdjustementAction( product )">
                                    <div class="text-xs cursor-pointer border-b border-dashed border-info-secondary py-1">
                                        <span class="text-xs">{{ __( 'Operation:' ) }}</span>&nbsp;
                                        <span class="">
                                            {{ getAdjustActionLabel( product.adjust_action ) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="product.accurate_tracking === 1" class="px-2 w-1/2 md:w-auto" @click="selectProcurement( product )">
                                    <div class="text-xs cursor-pointer border-b border-dashed border-info-secondary py-1">
                                        <span class="text-xs">{{ __( 'Procurement:' ) }}</span>&nbsp;
                                        <span class="">
                                            {{  product.procurement_history.filter( action => action.value === product.procurement_product_id )[0].label }}
                                        </span>
                                    </div>
                                </div>
                                <div class="px-2 w-1/2 md:w-auto" @click="provideReason( product )">
                                    <div class="text-xs cursor-pointer border-b border-dashed border-info-secondary py-1">
                                        <span class="text-xs">{{ __( 'Reason:' ) }}</span>&nbsp;
                                        <span v-if="product.adjust_reason">
                                            {{ __( 'Provided' ) }}
                                        </span>
                                        <span v-else="product.adjust_reason">
                                            {{ __( 'Not Provided' ) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="px-2 w-1/2 md:w-auto" @click="removeProduct( product )">
                                    <div class="text-xs cursor-pointer border-b border-dashed border-danger-secondary py-1">
                                        <span class="text-xs">{{ __( 'Remove' ) }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="p-2 border hidden md:table-cell" @click="openQuantityPopup( product )">
                            <div class="flex items-center justify-center cursor-pointer">
                                <span class="border-b border-dashed border-info-secondary py-2 px-4">{{ product.adjust_quantity }}</span>
                            </div>
                        </td>
                        <td class="p-2 border hidden md:table-cell">
                            <div class="flex items-center justify-center">
                                <span class="border-b border-dashed border-info-secondary py-2 px-4">{{ nsCurrency( product.adjust_value ) }}</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="ns-box-footer p-2 flex justify-end">
                <div class="-mx-2 flex">
                    <div class="px-2">
                        <ns-button v-if="products.filter( p => p.selected ).length > 0" @click="deleteSelectedProducts()" type="error">
                            <div class="flex items-center justify-center h-6">
                                <i class="las la-trash"></i>
                            </div>
                            <span class="hidden md:inline-block">{{ __( 'Remove Selected' ) }}</span>
                        </ns-button>
                    </div>
                    <div class="px-2">
                        <ns-button @click="proceedStockAdjustment()" type="info">{{ __( 'Proceed' ) }}</ns-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>