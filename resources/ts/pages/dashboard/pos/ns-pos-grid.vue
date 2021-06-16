<template>
    <div id="pos-grid" class="flex-auto flex flex-col">
        <div id="tools" class="flex pl-2" v-if="visibleSection === 'grid'">
            <div @click="switchTo( 'cart' )" class="flex cursor-pointer rounded-tl-lg rounded-tr-lg px-3 py-2 bg-gray-300 border-t border-r border-l border-gray-300 text-gray-600">
                <span>Cart</span>
                <span v-if="order" class="flex items-center justify-center text-sm rounded-full h-6 w-6 bg-green-500 text-white ml-1">{{ order.products.length }}</span>
            </div>
            <div @click="switchTo( 'grid' )" class="cursor-pointer rounded-tl-lg rounded-tr-lg px-3 py-2 bg-white font-semibold text-gray-700">
                Products
            </div>
        </div>
        <div class="rounded shadow bg-white overflow-hidden flex-auto flex flex-col">
            <div id="grid-header" class="p-2 border-b border-gray-200">
                <div class="border rounded flex border-gray-300 overflow-hidden">
                    <button @click="openSearchPopup()" class="w-10 h-10 bg-gray-200 border-r border-gray-300 outline-none">
                        <i class="las la-search"></i>
                    </button>
                    <button @click="autoFocus = ! autoFocus" :class="autoFocus ? 'pos-button-clicked bg-gray-300' : 'bg-gray-200'" class="outline-none w-10 h-10 border-r border-gray-300">
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
                <div v-if="! rebuildGridComplete" class="h-full w-full flex-col flex items-center justify-center">
                    <ns-spinner></ns-spinner>
                    <span class="text-gray-600 my-2">Rebuilding...</span>
                </div>
                <template v-if="rebuildGridComplete">

                    <VirtualCollection
                        :cellSizeAndPositionGetter="cellSizeAndPositionGetter"
                        :collection="categories"
                        :height="gridItemsHeight"
                        :width="gridItemsWidth"
                        v-if="hasCategories"
                    >
                        <div slot="cell" class="w-full h-full" slot-scope="{ data }">
                            <div @click="loadCategories( data )" :key="data.id" class="hover:bg-gray-200 w-full h-full cursor-pointer border border-gray-200 flex flex-col items-center justify-center overflow-hidden">
                                <div class="h-full w-full flex items-center justify-center">
                                    <img v-if="data.preview_url" :src="data.preview_url" class="object-cover h-full" :alt="data.name">
                                    <i class="las la-image text-gray-600 text-6xl" v-if="! data.preview_url"></i>
                                </div>
                                <div class="h-0 w-full">
                                    <div class="relative w-full flex items-center justify-center -top-10 h-20 py-2" style="background:rgb(255 255 255 / 73%)">
                                        <h3 class="text-sm font-bold text-gray-700 py-2 text-center">{{ data.name }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </VirtualCollection>
                    <VirtualCollection
                        :cellSizeAndPositionGetter="cellSizeAndPositionGetter"
                        :collection="products"
                        :height="gridItemsHeight"
                        :width="gridItemsWidth"
                        v-if="! hasCategories"
                    >
                        <div slot="cell" class="w-full h-full" slot-scope="{ data }">
                            <div @click="addToTheCart( data )" :key="data.id" class="hover:bg-gray-200 w-full h-full cursor-pointer border border-gray-200 flex flex-col items-center justify-center overflow-hidden">
                                <div class="h-full w-full flex items-center justify-center overflow-hidden">
                                    <img v-if="data.galleries && data.galleries.filter( i => i.featured === 1 ).length > 0" :src="data.galleries.filter( i => i.featured === 1 )[0].url" class="object-cover h-full" :alt="data.name">
                                    <i v-if="! data.galleries || data.galleries.filter( i => i.featured === 1 ).length === 0" class="las la-image text-gray-600 text-6xl"></i>
                                </div>
                                <div class="h-0 w-full">
                                    <div class="relative w-full flex flex-col items-center justify-center -top-10 h-20 p-2" style="background:rgb(255 255 255 / 73%)">
                                        <h3 class="text-sm text-gray-700 text-center w-full">{{ data.name }}</h3>
                                        <span class="text-sm text-gray-600" v-if="data.unit_quantities && data.unit_quantities.length === 1">{{ data.unit_quantities[0].sale_price | currency }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </VirtualCollection>
                </template>
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
            items: Array.from({length: 1000}, (_, index) => ({ data: '#' + index })),
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
            gridItemsWidth: 0,
            gridItemsHeight:0,
            screenSubscriber: null,
            rebuildGridTimeout: null,
            rebuildGridComplete: false,
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
            }, 200 );
        }
    },
    mounted() {
        this.loadCategories();
        this.breadcrumbsSubsribe        =   POS.breadcrumbs.subscribe( ( breadcrumbs ) => {
            this.breadcrumbs            =   breadcrumbs;
        });
        this.visibleSectionSubscriber   =   POS.visibleSection.subscribe( section => {
            this.visibleSection         =   section;
        });

        /**
         * Screen subscriber, this ensure the POS
         * can quickly detect the type of the viewport.
         */
        this.screenSubscriber       =   POS.screen.subscribe( screen => {
            clearTimeout( this.rebuildGridTimeout );
            this.rebuildGridComplete    =   false;
            this.rebuildGridTimeout     =   setTimeout( () => {
                this.rebuildGridComplete     =   true;
                this.computeGridWidth();
            }, 500 );
        });

        this.orderSubscription      =   POS.order.subscribe( order => this.order = order );

        this.interval   =   setInterval( () => this.checkFocus(), 500 );
    },
    destroyed() {
        this.orderSubscription.unsubscribe();
        this.breadcrumbsSubsribe.unsubscribe();
        this.visibleSectionSubscriber.unsubscribe();
        this.screenSubscriber.unsubscribe();
        clearInterval( this.interval );
    },
    methods: {
        switchTo,

        computeGridWidth() {
            if ( document.getElementById( 'grid-items' ) !== null ) {
                this.gridItemsWidth     =   document.getElementById( 'grid-items' ).offsetWidth;
                this.gridItemsHeight    =   document.getElementById( 'grid-items' ).offsetHeight;
            }
        },

        cellSizeAndPositionGetter(item, index) {
            const responsive    =   {
                xs: {
                    width: this.gridItemsWidth / 2,
                    items: 2,
                    height: 200,
                },
                sm: {
                    width: this.gridItemsWidth / 2,
                    items: 2,
                    height: 200,
                },
                md: {
                    width: this.gridItemsWidth / 3,
                    items: 3,
                    height: 150,
                },
                lg: {
                    width: this.gridItemsWidth / 4,
                    items: 4,
                    height: 150,
                },
                xl: {
                    width: this.gridItemsWidth / 6,
                    items: 6,
                    height: 150,
                }
            }

            const wrapperWidth  =   responsive[ POS.responsive.screenIs ].width;
            const wrapperHeight =   responsive[ POS.responsive.screenIs ].height;
            const scrollWidth   =   0; // ( 50 / responsive[ POS.responsive.screenIs ].items );            

            return {
                width: wrapperWidth - scrollWidth,
                height: wrapperHeight,
                x: ( ( index % responsive[ POS.responsive.screenIs ].items ) * ( wrapperWidth ) ) - scrollWidth,
                y: parseInt( index / responsive[ POS.responsive.screenIs ].items ) * wrapperHeight
            }
        },

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
                    this.categories         =   result.categories.map( category => {
                        return {
                            data    :   category
                        }
                    });
                    this.products           =   result.products.map( product => {
                        return {
                            data: product
                        }
                    });
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