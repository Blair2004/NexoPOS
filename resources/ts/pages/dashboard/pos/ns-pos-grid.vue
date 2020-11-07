<template>
    <div id="pos-grid" class="flex-auto flex flex-col">
        <div id="tools" class="flex pl-2" v-if="visibleSection === 'grid'">
            <div @click="switchTo( 'cart' )" class="flex cursor-pointer rounded-tl-lg rounded-tr-lg px-3 py-2 bg-gray-300 border-t border-r border-l border-gray-400 text-gray-600">
                <span>Cart</span>
                <span v-if="order" class="flex items-center justify-center text-sm rounded-full h-6 w-6 bg-green-500 text-white ml-1">{{ order.products.length }}</span>
            </div>
            <div @click="switchTo( 'grid' )" class="cursor-pointer rounded-tl-lg rounded-tr-lg px-3 py-2 bg-white font-semibold text-gray-700">
                Products
            </div>
        </div>
        <div class="rounded shadow bg-white overflow-hidden flex-auto flex flex-col">
            <div id="grid-header" class="p-2 border-b border-gray-200">
                <div class="border rounded flex border-gray-400 overflow-hidden">
                    <button @click="openSearchPopup()" class="w-10 h-10 bg-gray-200 border-r border-gray-400 outline-none">
                        <i class="las la-search"></i>
                    </button>
                    <button @click="autoFocus = ! autoFocus" :class="autoFocus ? 'pos-button-clicked bg-gray-400' : 'bg-gray-200'" class="outline-none w-10 h-10 border-r border-gray-400">
                        <i class="las la-barcode"></i>
                    </button>
                    <input ref="search" v-model="barcode" type="text" class="flex-auto outline-none px-2 bg-gray-100">
                </div>
            </div>
            <div id="grid-breadscrumb" class="p-2 border-gray-200">
                <ul class="flex">
                    <li><a @click="loadCategories()" href="javascript:void(0)" class="px-3 text-gray-700">Home </a> <i class="las la-angle-right"></i> </li>
                    <li><a @click="loadCategories( bread )" v-for="bread of breadcrumbs" :key="bread.id" href="javascript:void(0)" class="px-3 text-gray-700">{{ bread.name }} <i class="las la-angle-right"></i></a></li>
                </ul>
            </div>
            <div id="grid-items" class="overflow-hidden h-full flex-col flex">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-0 overflow-y-auto">
                    
                    <!-- Loop Products Or Categories -->

                    <template v-if="previousCategory !== false">
                        <div @click="loadCategories( previousCategory )" class="hover:bg-gray-200 cursor-pointer border h-32 lg:h-40 border-gray-200 flex flex-col items-center justify-center">
                            <div class="h-full w-full p-2 flex items-center justify-center">
                                <i class="las la-undo font-bold text-5xl"></i>
                            </div>
                        </div>
                    </template>

                    <template v-if="hasCategories">
                        <div @click="loadCategories( category )" :key="category.id" v-for="category of categories" class="hover:bg-gray-200 cursor-pointer border h-32 lg:h-40 border-gray-200 flex flex-col items-center justify-center overflow-hidden">
                            <div class="h-full w-full flex items-center justify-center">
                                <img v-if="category.preview_url" :src="category.preview_url" class="object-cover h-full" :alt="category.name">
                                <i class="las la-image text-gray-600 text-6xl" v-if="! category.preview_url"></i>
                            </div>
                            <div class="h-0 w-full">
                                <div class="relative w-full flex items-center justify-center -top-10 h-20 py-2" style="background:rgb(255 255 255 / 73%)">
                                    <h3 class="text-sm font-bold text-gray-700 py-2 text-center">{{ category.name }}</h3>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Looping Products -->

                    <template v-if="! hasCategories">
                        <div @click="addToTheCart( product )" :key="product.id" v-for="product of products"  class="hover:bg-gray-200 cursor-pointer border h-32 lg:h-40 border-gray-200 flex flex-col items-center justify-center">
                            <div class="h-full w-full flex items-center justify-center overflow-hidden">
                                <img v-if="product.galleries.filter( i => i.featured === 1 ).length > 0" :src="product.galleries.filter( i => i.featured === 1 )[0].url" class="object-cover h-full" :alt="product.name">
                                <i v-if="product.galleries.filter( i => i.featured === 1 ).length === 0" class="las la-image text-gray-600 text-6xl"></i>
                            </div>
                            <div class="h-0 w-full">
                                <div class="relative w-full flex flex-col items-start justify-center -top-10 h-20 p-2" style="background:rgb(255 255 255 / 73%)">
                                    <h3 class="text-sm text-gray-700 text-center w-full">{{ product.name }}</h3>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <!-- End Loop -->

                </div>
            </div>
        </div>
    </div>
</template>
<script >
import Vue from 'vue';
import { nsHttpClient, nsSnackBar } from '../../../bootstrap'
import switchTo from "@/libraries/pos-section-switch";
import nsPosSearchProductVue from '@/popups/ns-pos-search-product.vue';

export default {
    name: 'ns-pos-grid',
    data() {
        return {
            products: [],
            categories: [],
            breadcrumbs: [],
            autoFocus: false,
            barcode: '',
            previousCategory: null,
            order: null,
            visibleSection: null,
            breadcrumbsSubsribe: null,
            orderSubscription: null,
            visibleSectionSubscriber: null,
            currentCategory: null,
            interval: null,
            searchTimeout: null,
        }
    },
    computed: {
        hasCategories() {
            return this.categories.length > 0;
        }
    },
    watch: {
        barcode() {
            clearTimeout( this.searchTimeout );
            this.searchTimeout  =   setTimeout( () => {
                this.submitSearch( this.barcode );
            }, 1000 );
        }
    },
    mounted() {
        this.loadCategories();
        this.breadcrumbsSubsribe    =   POS.breadcrumbs.subscribe( ( breadcrumbs ) => {
            this.breadcrumbs     =   breadcrumbs;
        });
        this.visibleSectionSubscriber   =   POS.visibleSection.subscribe( section => {
            this.visibleSection     =   section;
        });
        this.orderSubscription      =   POS.order.subscribe( order => this.order = order );

        this.interval   =   setInterval( () => this.checkFocus(), 500 );
    },
    destroyed() {
        this.orderSubscription.unsubscribe();
        this.breadcrumbsSubsribe.unsubscribe();
        this.visibleSectionSubscriber.unsubscribe();
        clearInterval( this.interval );
    },
    methods: {
        switchTo,

        openSearchPopup() {
            Popup.show( nsPosSearchProductVue );
        },

        submitSearch( value ) {
            if ( value.length > 0 ) {
                nsHttpClient.get( `/api/nexopos/v4/products/search/using-barcode/${value}` )
                    .subscribe( result => {
                        this.barcode     =   '';
                        POS.addToCart( result.product );
                    }, ( error ) => {
                        this.barcode     =   '';
                        nsSnackBar.error( error.message ).subscribe();
                    })
            }
        },

        checkFocus() {
            if ( this.autoFocus ) {
                const popup     =   document.querySelectorAll( '.is-popup' );
                if ( popup.length === 0 ) {
                    this.$refs.search.focus();
                }
            }
        },
        
        loadCategories( parent ) {
            nsHttpClient.get( `/api/nexopos/v4/categories/pos/${ parent ? parent.id : ''}` )
                .subscribe( (result ) => {
                    this.categories         =   result.categories;
                    this.products           =   result.products;
                    this.previousCategory   =   result.previousCategory;
                    this.currentCategory    =   result.currentCategory;
                    this.updateBreadCrumb( this.currentCategory );
                });
        },

        updateBreadCrumb( parent ) {
            if ( parent ) {
                const index     =   this.breadcrumb.filter( bread => bread.id === parent.id );
    
                /**
                 * this means, we're trying to navigate
                 * through something that has already been 
                 * added to the breadcrumb
                 */
                if ( index.length > 0 ) {
                    let allow       =   true;
                    const prior     =   this.breadcrumb.filter( bread => {
                        if ( bread.id === index[0].id && allow ) {
                            allow   =   false;
                            return true;
                        }

                        return allow;
                    });
                    this.breadcrumb     =   prior;
                } else {
                    this.breadcrumb.push( parent );
                } 
    
            } else {
                this.breadcrumb     =   [];    
            }

            POS.breadcrumbs.next( this.breadcrumb );
        },
    
        addToTheCart( product ) {
            POS.addToCart( product );
        }
    }
}
</script>