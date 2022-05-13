<template>
    <div class="flex flex-col px-4 w-full">
        <div class="md:-mx-4 flex md:flex-row">
            <div class="md:px-4 w-full md:w-1/3">
                <ns-field :field="field" v-for="(field,index) of fields" :key="index"></ns-field>
            </div>
            <div class="md:px-4 w-full md:w-2/3">
                <div class="input-group border-2 rounded info flex w-full">
                    <input v-model="searchValue" type="text" class="flex-auto p-2 outline-none">
                </div>
                <div class="h-0 relative" v-if="results.length > 0">
                    <ul class="ns-vertical-menu absolute w-full">
                        <li class="p-2 border-b cursor-pointer">Some result</li>
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
                            <tr>
                                <td class="border p-2">
                                    <h3 class="font-bold">Product Name</h3>
                                    <ul>
                                        <li class="text-sm flex justify-between">
                                            <span>{{ __( 'Unit' ) }}:</span> 
                                            <span class="cursor-pointer border-b border-dashed border-info-secondary">10</span>
                                        </li>
                                        <li class="text-sm flex justify-between">
                                            <span>{{ __( 'Quantity' ) }}:</span> 
                                            <span class="cursor-pointer border-b border-dashed border-info-secondary">10</span>
                                        </li>
                                        <li class="text-sm flex justify-between">
                                            <span>{{ __( 'Price' ) }}:</span> 
                                            <span class="cursor-pointer border-b border-dashed border-info-secondary">10</span>
                                        </li>
                                    </ul>
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
import { nsHttpClient } from '@/bootstrap';
export default {
    name: 'ns-product-group',
    props: [ 'fields' ],
    watch:{
        searchValue() {
            clearTimeout( this.searchTimeout );

            this.searchTimeout  =   setTimeout( () => {
                this.searchProducts( this.searchProduct );
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
        searchProducts( search ) {
            nsHttpClient.post( `/api/nexopos/v4/products/search`, { 
                search, 
                arguments: {
                    type: {
                        comparison: '<>',
                        value: 'grouped'
                    }
                }
            })
        }
    }
}
</script>