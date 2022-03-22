<template>
    <div id="pos-grid" class="flex-auto flex flex-col">
        <div id="tools" class="flex pl-2" v-if="visibleSection === 'grid'">
            <div @click="switchTo( 'cart' )" class="switch-cart flex cursor-pointer rounded-tl-lg rounded-tr-lg px-3 py-2 border-t border-r border-l">
                <span>{{ __( 'Cart' ) }}</span>
                <span v-if="order" class="products-count flex items-center justify-center text-sm rounded-full h-6 w-6 ml-1">{{ order.products.length }}</span>
            </div>
            <div @click="switchTo( 'grid' )" class="switch-grid cursor-pointer rounded-tl-lg rounded-tr-lg px-3 py-2 font-semibold">
                {{ __( 'Products' ) }}
            </div>
        </div>
        <div id="grid-container" class="rounded shadow  overflow-hidden flex-auto flex flex-col">
            <div id="grid-header" class="p-2 border-b ">
                <div class="border rounded flex  overflow-hidden">
                    <button :title="__( 'Search for products.' )" @click="openSearchPopup()" class="w-10 h-10 border-r  outline-none">
                        <i class="las la-search"></i>
                    </button>
                    <button :title="__( 'Toggle merging similar products.' )" @click="posToggleMerge()" :class="settings.pos_items_merge ? 'pos-button-clicked' : ''" class="outline-none w-10 h-10 border-r ">
                        <i class="las la-compress-arrows-alt"></i>
                    </button>
                    <button :title="__( 'Toggle auto focus.' )" @click="autoFocus = ! autoFocus" :class="autoFocus ? 'pos-button-clicked' : ''" class="outline-none w-10 h-10 border-r ">
                        <i class="las la-barcode"></i>
                    </button>
                    <input ref="search" v-model="barcode" type="text" class="flex-auto outline-none px-2 ">
                </div>
            </div>
            <div style="height: 0px">
                <div v-if="isLoading" class="fade-in-entrance ns-loader">
                    <div class="bar"></div>
                </div>
            </div>
            <div id="grid-breadscrumb" class="p-2">
                <ul class="flex">
                    <li><a @click="loadCategories()" href="javascript:void(0)" class="px-3 ">{{ __( 'Home' ) }} </a> <i class="las la-angle-right"></i> </li>
                    <li><a @click="loadCategories( bread )" v-for="bread of breadcrumbs" :key="bread.id" href="javascript:void(0)" class="px-3">{{ bread.name }} <i class="las la-angle-right"></i></a></li>
                </ul>
            </div>
            <div id="grid-items" class="overflow-hidden h-full flex-col flex">
                <div v-if="! rebuildGridComplete" class="h-full w-full flex-col flex items-center justify-center">
                    <ns-spinner></ns-spinner>
                    <span class="my-2">{{ __( 'Rebuilding...' ) }}</span>
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
                            <div @click="loadCategories( data )" :key="data.id" class="cell-item w-full h-full cursor-pointer border flex flex-col items-center justify-center overflow-hidden">
                                <div class="h-full w-full flex items-center justify-center">
                                    <img v-if="data.preview_url" :src="data.preview_url" class="object-cover h-full" :alt="data.name">
                                    <i class="las la-image text-6xl" v-if="! data.preview_url"></i>
                                </div>
                                <div class="h-0 w-full">
                                    <div class="cell-item-label relative w-full flex items-center justify-center -top-10 h-20 py-2">
                                        <h3 class="text-sm font-bold py-2 text-center">{{ data.name }}</h3>
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
                            <div @click="addToTheCart( data )" :key="data.id" class="cell-item w-full h-full cursor-pointer border flex flex-col items-center justify-center overflow-hidden">
                                <div class="h-full w-full flex items-center justify-center overflow-hidden">
                                    <img v-if="data.galleries && data.galleries.filter( i => i.featured === 1 ).length > 0" :src="data.galleries.filter( i => i.featured === 1 )[0].url" class="object-cover h-full" :alt="data.name">
                                    <i v-if="! data.galleries || data.galleries.filter( i => i.featured === 1 ).length === 0" class="las la-image text-6xl"></i>
                                </div>
                                <div class="h-0 w-full">
                                    <div class="cell-item-label relative w-full flex flex-col items-center justify-center -top-10 h-20 p-2">
                                        <h3 class="text-sm text-center w-full">{{ data.name }}</h3>
                                        <span class="text-sm" v-if="data.unit_quantities && data.unit_quantities.length === 1">{{ data.unit_quantities[0].sale_price | currency }}</span>
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
import { nsHttpClient, nsSnackBar } from '../../../bootstrap'
import switchTo from "@/libraries/pos-section-switch";
import nsPosSearchProductVue from '@/popups/ns-pos-search-product.vue';
import { __ } from '@/libraries/lang';

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
            settings: false,
            settingsSubscriber: null,
            interval: null,
            searchTimeout: null,
            gridItemsWidth: 0,
            gridItemsHeight:0,
            screenSubscriber: null,
            rebuildGridTimeout: null,
            rebuildGridComplete: false,
            isLoading: false,
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

        this.settingsSubscriber         =   POS.settings.subscribe( settings => {
            this.settings               =   settings;
            console.log( settings );
        });

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
        this.settingsSubscriber.unsubscribe();
        clearInterval( this.interval );
    },
    methods: {
        __, 

        switchTo,

        posToggleMerge() {
            POS.set( 'pos_items_merge', ! this.settings.pos_items_merge );
        },

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
            this.isLoading  =   true;
            nsHttpClient.get( `/api/nexopos/v4/categories/pos/${ parent ? parent.id : ''}` )
                .subscribe({
                    next: (result ) => {
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
                        this.isLoading  =   false;
                    },
                    error: ( error ) => {
                        this.isLoading  =   false;
                        return nsSnackBar.error( __( 'An unexpected error occured.' ) ).subscribe();
                    }
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