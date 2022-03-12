<script>
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { Popup } from '@/libraries/popup';
import nsProcurementQuantityVue from '@/popups/ns-procurement-quantity.vue';
import nsPosConfirmPopupVue from '@/popups/ns-pos-confirm-popup.vue';
import nsPromptPopupVue from '@/popups/ns-prompt-popup.vue';
import { __ } from '@/libraries/lang';
import { forkJoin } from 'rxjs';
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
        console.log( this.actions );
    },
    methods: {
        __,

        searchProduct( argument ) {
            if ( argument.length > 0 ) {
                nsHttpClient.post( '/api/nexopos/v4/procurements/products/search-procurement-product', { argument })
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
                                return nsSnackBar.error( __( 'Looks like no products matched the searched term.' ) ).subscribe();
                            }
                        } else if ( result.from === 'procurements' ) {
                            if ( result.product === null ) {
                                this.closeSearch();
                                return nsSnackBar.error( __( 'Looks like no products matched the searched term.' ) ).subscribe();
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

            const finalProduct                  =   new Object;
            product.unit_quantity.unit          =   product.unit;
            finalProduct.quantities             =   [ product.unit_quantity ];
            finalProduct.name                   =   product.name;
            finalProduct.adjust_unit            =   product.unit_quantity;

            finalProduct.adjust_quantity            =   1;
            finalProduct.adjust_action              =   '',
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
 
            this.products.push( finalProduct );
            this.closeSearch();
        },

        addSuggestion( suggestion ) {
            forkJoin([
                nsHttpClient.get( `/api/nexopos/v4/products/${suggestion.id}/units/quantities` ),
                // nsHttpClient.get( `/api/nexopos/v4/products/${suggestion.id}/procurements` )
            ]).subscribe( result => {
                    if ( result[0].length > 0 ) {
                        suggestion.quantities                       =   result[0];
                        suggestion.adjust_quantity                  =   1;
                        suggestion.adjust_action                    =   '',
                        suggestion.adjust_reason                    =   '',
                        suggestion.adjust_unit                      =   '',
                        suggestion.adjust_value                     =   0;
                        suggestion.procurement_product_id           =   0;

                        this.products.push( suggestion );
                        this.closeSearch();
                    } else {
                        return nsSnackBar.error( __( `This product does't have any stock to adjust.` ) ).subscribe();
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
                if ( ! [ 'added' ].includes( product.adjust_action ) ) {
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
                        nsHttpClient.post( '/api/nexopos/v4/products/adjustments', { products: this.products })
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
            <input @keyup.esc="closeSearch()" v-model="search" type="text" class="p-2 flex-auto outline-none">
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
                        <td width="120" class="p-2 text-center">{{ __( 'Unit' ) }}</td>
                        <td width="120" class="p-2 text-center">{{ __( 'Operation' ) }}</td>
                        <td width="120" class="p-2 text-center">{{ __( 'Procurement' ) }}</td>
                        <td width="120" class="p-2 text-center">{{ __( 'Quantity' ) }}</td>
                        <td width="120" class="p-2 text-center">{{ __( 'Value' ) }}</td>
                        <td width="150" class="p-2 text-center">{{ __( 'Actions' ) }}</td>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="products.length === 0">
                        <td class="p-2 text-center" colspan="6">{{ __( 'Search and add some products' ) }}</td>
                    </tr>
                    <tr :key="product.id" v-for="product of products">
                        <td class="p-2">{{ product.name }} ({{ ( product.accurate_tracking === 1 ? product.available_quantity : product.adjust_unit.quantity ) || 0 }})</td>
                        <td class="p-2">
                            <div class="input-group border-2 info">
                                <select @change="recalculateProduct( product )" v-model="product.adjust_unit" class="outline-none p-2 w-full">
                                    <option :key="quantity.id" v-for="quantity of product.quantities" :value="quantity">{{ quantity.unit.name }}</option>
                                </select>
                            </div>
                        </td>
                        <td class="p-2">
                            <div class="input-group border-2 info">
                                <select @change="recalculateProduct( product )" v-model="product.adjust_action" name="" id="" class="outline-none p-2 w-full">
                                    <option v-for="action of actions" :key="action.value" :value="action.value">{{ action.label }}</option>
                                </select>
                            </div>
                        </td>
                        <td class="p-2">
                            <div v-if="product.accurate_tracking === 1" class="input-group border-2 info">
                                <select @change="recalculateProduct( product )" v-model="product.procurement_product_id" name="" id="" class="outline-none p-2 bg-white w-full">
                                    <option v-for="action of product.procurement_history" :key="action.value" :value="action.value">{{ action.label }}</option>
                                </select>
                            </div>
                        </td>
                        <td class="p-2 flex items-center justify-center cursor-pointer" @click="openQuantityPopup( product )">
                            <span class="border-b border-dashed border-blue-400 py-2 px-4">{{ product.adjust_quantity }}</span>
                        </td>
                        <td class="p-2">
                            <span class="border-b border-dashed border-blue-400 py-2 px-4">{{ product.adjust_value | currency }}</span>
                        </td>
                        <td class="p-2">
                            <div class="-mx-1 flex justify-end">
                                <div class="px-1">
                                    <button @click="provideReason( product )" class="ns-inset-button active info border outline-none rounded-full shadow h-10 w-10">
                                        <i class="las la-comment-dots"></i>
                                    </button>
                                </div>
                                <div class="px-1">
                                    <button @click="removeProduct( product )" class="ns-inset-button active error border outline-none rounded-full shadow h-10 w-10">
                                        <i class="las la-times"></i>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="border-t ns-box-footer p-2 flex justify-end">
                <ns-button @click="proceedStockAdjustment()" type="info">{{ __( 'Proceed' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>