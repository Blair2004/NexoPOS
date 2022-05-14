<template>
    <div class="flex flex-col px-4 w-full">
        <div class="md:-mx-4 flex flex-col md:flex-row">
            <div class="md:px-4 w-full md:w-1/3">
                <ns-field :field="field" v-for="(field,index) of fields" :key="index"></ns-field>
            </div>
            <div class="md:px-4 w-full md:w-2/3">
                <div class="input-group border-2 rounded info flex w-full">
                    <input :placeholder="__( 'Search products...' )" v-model="searchValue" type="text" class="flex-auto p-2 outline-none">
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
                                <th class="border">{{ __( 'Products' ) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(product,index) of products" :key="index">
                                <td class="border p-2">
                                    <h3 class="font-bold">{{ product.name }}</h3>
                                    <ul>
                                        <li class="flex justify-between">
                                            <span>{{ __( 'Unit' ) }}:</span> 
                                            <span class="cursor-pointer border-b border-dashed border-info-secondary">{{ product.unit.name }}</span>
                                        </li>
                                        <li class="flex justify-between">
                                            <span>{{ __( 'Quantity' ) }}:</span> 
                                            <span class="cursor-pointer border-b border-dashed border-info-secondary">{{ product.quantity }}</span>
                                        </li>
                                        <li class="flex justify-between">
                                            <span>{{ __( 'Price' ) }}:</span> 
                                            <span class="cursor-pointer border-b border-dashed border-info-secondary">{{ product.sale_price }}</span>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td class="border p-2 text-center">
                                    {{ __( 'No product are added to this group.' ) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { Popup } from '@/libraries/popup';
import nsSelectPopupVue from '@/popups/ns-select-popup.vue';
export default {
    name: 'ns-product-group',
    props: [ 'fields' ],
    watch:{
        searchValue() {
            clearTimeout( this.searchTimeout );

            this.searchTimeout  =   setTimeout( () => {
                this.searchProducts( this.searchValue );
            }, 1000 );
        }
    },
    mounted() {
        console.log( this.fields );
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
        async addResult( result ) {
            this.searchValue    =   '';

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
                    unit_id: unitQuantity[0].unit.id,
                    unit: unitQuantity[0].unit,
                    quantity: 1,
                    sale_price: unitQuantity[0].sale_price
                });

            } catch( exception ) {
                console.log( exception );
            }
        },
        searchProducts( search ) {
            nsHttpClient.post( `/api/nexopos/v4/products/search`, { 
                search, 
                arguments: {
                    type: {
                        comparison: '<>',
                        value: 'grouped'
                    }
                }
            }).subscribe({
                next: results => {
                    this.results    =   results;
                },
                error: error => {
                    nsSnackBar.error( error.message || __( 'An unexpected error occured' ), __( 'Ok' ), { duration: 3000 }).subscribe();
                }
            })
        }
    }
}
</script>