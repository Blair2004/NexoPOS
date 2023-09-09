<template>
    <div id="product-search" class="ns-box shadow-lg w-95vw h-95vh md:h-3/5-screen md:w-2/4-screen flex flex-col overflow-hidden">
        <div class="p-2 border-b ns-box-header flex justify-between items-center">
            <h3 class="text-primary">{{ __( 'Search Product' ) }}</h3>
            <div>
                <ns-close-button @click="popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto overflow-hidden flex flex-col">
            <div class="p-2 border-b ns-box-body">
                <div class="flex input-group info border-2 rounded overflow-hidden">
                    <input @keyup.enter="search()" v-model="searchValue" ref="searchField" type="text" class="p-2  outline-none flex-auto text-primary">
                    <button @click="search()" class="px-2">{{ __( 'Search' ) }}</button>
                </div>
            </div>
            <div class="overflow-y-auto ns-scrollbar flex-auto relative">
                <ul class="ns-vertical-menu">
                    <li v-for="product of products" :key="product.id" @click="addToCart( product )" class="cursor-pointer p-2 flex justify-between border-b">
                        <div class="">
                            <h2 class="text-primary">{{ product.name }}</h2>
                            <small class="text-soft-secondary text-xs">{{ product.category.name }}</small>
                        </div>
                        <div></div>
                    </li>
                </ul>
                <ul v-if="products.length === 0">
                    <li class="text-primary text-center p-2">{{ __( 'There is nothing to display. Have you started the search ?' ) }}</li>
                </ul>
                <div v-if="isLoading" class="absolute h-full w-full flex items-center justify-center z-10 top-0" style="background: rgb(187 203 214 / 29%)">
                    <ns-spinner></ns-spinner>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import popupCloser from "~/libraries/popup-closer";
import popupResolver from '~/libraries/popup-resolver';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import nsPosConfirmPopupVue from './ns-pos-confirm-popup.vue';
export default {
    name: 'ns-pos-search-product',
    props: [ 'popup' ],
    data() {
        return {
            searchValue: '',
            products: [],
            isLoading: false,
            debounce: null,
        }
    },
    watch:{
        searchValue() {
            clearTimeout( this.debounce );
            this.debounce   =   setTimeout( () => {
                this.search();
            }, 500 );
        }
    },
    mounted() {
        this.$refs.searchField.focus();

        /**
         * hotkey can't catch esc if
         * the focus is on the field.
         */
        this.$refs.searchField.addEventListener( 'keydown', ( e ) => {
            if ( e.keyCode === 27 ) {
                this.popupResolver( false );
            }
        });

        this.popupCloser();
    },
    methods: {
        __,
        
        popupCloser,
        popupResolver,

        addToCart( product ) {
            this.popup.close();

            if ( parseInt( product.accurate_tracking ) === 1 ) {
                return Popup.show( nsPosConfirmPopupVue, {
                    title: __( 'Unable to add the product' ),
                    message: __( 'The product "{product}" can\'t be added from a search field, as "Accurate Tracking" is enabled. Would you like to learn more ?' ).replace( '{product}', product.name ),
                    onAction: ( action ) => {
                        if ( action ) {
                            window.open( 'https://my.nexopos.com/en/documentation/troubleshooting/accurate-tracking', '_blank' );
                        }
                    }
                });
            }

            POS.addToCart( product );
        },

        search() {
            this.isLoading  =   true;
            nsHttpClient.post( '/api/products/search', { search: this.searchValue })
                .subscribe({
                    next: result => {
                        this.isLoading  =   false;
                        this.products   =   result;

                        if ( this.products.length === 1 ) {
                            this.addToCart( this.products[0] );
                        }

                        if ( this.products.length === 0 ) {
                            return nsSnackBar.info( __( 'No result to result match the search value provided.' ) ).subscribe();
                        }
                    },
                    error: ( error ) => {
                        this.isLoading  =   false;
                        nsSnackBar.error( error.message ).subscribe();
                    } 
                })
        }
    }
}
</script>