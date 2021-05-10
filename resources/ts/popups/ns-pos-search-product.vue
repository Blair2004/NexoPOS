<template>
    <div class="bg-white shadow-lg w-95vw h-95vh md:h-3/5-screen md:w-2/4-screen flex flex-col overflow-hidden">
        <div class="p-2 border-b border-gray-300 flex justify-between items-center">
            <h3 class="text-gray-700">{{ __( 'Search Product' ) }}</h3>
            <div>
                <ns-close-button @click="$popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto overflow-hidden flex flex-col">
            <div class="p-2 border-b border-gray-300">
                <div class="flex border-blue-400 border-2 rounded overflow-hidden">
                    <input @keyup.enter="search()" v-model="searchValue" ref="searchField" type="text" class="p-2  outline-none flex-auto text-gray-700 bg-blue-100">
                    <button @click="search()" class="px-2 bg-blue-400 text-white">{{ __( 'Search' ) }}</button>
                </div>
            </div>
            <div class="overflow-y-auto flex-auto relative">
                <ul>
                    <li v-for="product of products" :key="product.id" @click="addToCart( product )" class="hover:bg-blue-100 cursor-pointer p-2 flex justify-between border-b">
                        <div class="text-gray-700">
                            {{ product.name }}
                        </div>
                        <div></div>
                    </li>
                    <li v-if="products.length === 0" class="text-gray-700 text-center p-2">{{ __( 'There is nothing to display. Have you started the search ?' ) }}</li>
                </ul>
                <div v-if="isLoading" class="absolute h-full w-full flex items-center justify-center z-10 top-0" style="background: rgb(187 203 214 / 29%)">
                    <ns-spinner></ns-spinner>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import popupCloser from "@/libraries/popup-closer";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
export default {
    name: 'ns-pos-search-product',
    data() {
        return {
            searchValue: '',
            products: [],
            isLoading: false,
        }
    },
    mounted() {
        this.$refs.searchField.focus();
        this.popupCloser();
    },
    methods: {
        __,
        
        popupCloser,

        addToCart( product ) {
            POS.addToCart( product );
            this.$popup.close();
        },

        search() {
            this.isLoading  =   true;
            nsHttpClient.post( '/api/nexopos/v4/products/search', { search: this.searchValue })
                .subscribe( result => {
                    this.isLoading  =   false;
                    this.products   =   result;

                    if ( this.products.length === 1 ) {
                        this.addToCart( this.products[0] );
                    }

                }, ( error ) => {
                    this.isLoading  =   false;
                    nsSnackBar.error( error.message ).subscribe();
                })
        }
    }
}
</script>