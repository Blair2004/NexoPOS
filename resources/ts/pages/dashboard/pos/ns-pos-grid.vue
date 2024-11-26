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
                    <button :title="__( 'Toggle merging similar products.' )" @click="posToggleMerge()" :class="settings.ns_pos_items_merge ? 'pos-button-clicked' : ''" class="outline-none w-10 h-10 border-r ">
                        <i class="las la-compress-arrows-alt"></i>
                    </button>
                    <button :title="__( 'Toggle auto focus.' )" @click="options.ns_pos_force_autofocus = ! options.ns_pos_force_autofocus" :class="options.ns_pos_force_autofocus ? 'pos-button-clicked' : ''" class="outline-none w-10 h-10 border-r ">
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
            <div id="grid-items" class="overflow-y-auto h-full flex-col flex">
                <div v-if="hasCategories" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <div @click="loadCategories( category )" v-for="category of categories" :key="category.id" 
                        class="cell-item w-full h-36 cursor-pointer border flex flex-col items-center justify-center overflow-hidden relative">
                        <div class="h-full w-full flex items-center justify-center">
                            <img v-if="category.preview_url" :src="category.preview_url" class="object-cover h-full" :alt="category.name">
                            <i class="las la-image text-6xl" v-if="! category.preview_url"></i>
                        </div>
                        <div class="w-full absolute z-10 -bottom-10">
                            <div class="cell-item-label relative w-full flex items-center justify-center -top-10 h-20 py-2">
                                <h3 class="text-sm font-bold py-2 text-center">{{ category.name }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="! hasCategories && ! hasProducts && ! isLoading" class="h-full w-full flex flex-col items-center justify-center">
                    <i class="las la-frown-open text-8xl text-primary"></i>
                    <p class="w-1/2 md:w-2/3 text-center text-primary">
                        {{ __( 'Looks like there is either no products and no categories. How about creating those first to get started ?' ) }}
                    </p>
                    <br>
                    <ns-link target="blank" type="info" :href="createCategoryUrl">{{ __( 'Create Categories' ) }}</ns-link>
                </div>

                <div  v-if="! hasCategories" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <div @click="addToTheCart( product )" v-for="product of products" :key="product.id" 
                        class="cell-item w-full h-36 cursor-pointer border flex flex-col items-center justify-center overflow-hidden relative">
                        <div class="h-full w-full flex items-center justify-center overflow-hidden">
                            <img v-if="product.galleries && product.galleries.filter( i => i.featured ).length > 0" :src="product.galleries.filter( i => i.featured )[0].url" class="object-cover h-full" :alt="product.name"/>
                            <img v-else-if="hasNoFeatured( product )" :src="product.galleries[0].url" class="object-cover h-full" :alt="product.name"/>
                            <i v-else="! product.galleries || product.galleries.filter( i => i.featured ).length === 0" class="las la-image text-6xl"></i>
                        </div>
                        <div class="w-full absolute z-10 -bottom-10">
                            <div class="cell-item-label relative w-full flex flex-col items-center justify-center -top-10 h-20 p-2">
                                <h3 class="text-sm text-center w-full">{{ product.name }}</h3>
                                <template v-if="options.ns_pos_gross_price_used === 'yes'">
                                    <span class="text-sm" v-if="product.unit_quantities && product.unit_quantities.length === 1">
                                        {{ nsCurrency( product.unit_quantities[0].sale_price_without_tax ) }}
                                    </span>
                                </template>
                                <template v-if="options.ns_pos_gross_price_used === 'no'">
                                    <span class="text-sm" v-if="product.unit_quantities && product.unit_quantities.length === 1">
                                        {{ nsCurrency( product.unit_quantities[0].sale_price_with_tax ) }}
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script >
import { nsHttpClient, nsSnackBar } from '../../../bootstrap'
import switchTo from "~/libraries/pos-section-switch";
import nsPosSearchProductVue from '~/popups/ns-pos-search-product.vue';
import { __ } from '~/libraries/lang';
import { nsCurrency, nsRawCurrency } from '~/filters/currency';

export default {
    name: 'ns-pos-grid',
    data() {
        return {
            items: Array.from({length: 1000}, (_, index) => ({ data: '#' + index })),
            products: [],
            categories: [],
            breadcrumbs: [],
            barcode: '',
            previousCategory: null,
            order: null,
            visibleSection: null,
            breadcrumbsSubsribe: null,
            orderSubscription: null,
            visibleSectionSubscriber: null,
            currentCategory: null,
            settings: {},
            settingsSubscriber: null,
            options: false,
            optionsSubscriber: null,
            interval: null,
            searchTimeout: null,
            gridItemsWidth: 0,
            gridItemsHeight:0,
            isLoading: false,
        }
    },
    computed: {
        hasCategories() {
            return this.categories.length > 0;
        },
        hasProducts() {
            return this.products.length > 0;
        },
        createCategoryUrl() {
            // link to create category defined on OrdersController.
            return POS.settings.getValue().urls.categories_url; 
        }
    },
    watch: {
        options: {
            handler() {
                if ( this.options.ns_pos_force_autofocus ) {
                    clearTimeout( this.searchTimeout );

                    this.searchTimeout  =   setTimeout( () => {
                        this.submitSearch( this.barcode );
                    }, 200 );
                }
            },
            deep: true
        },
        barcode() {
            if ( this.options.ns_pos_force_autofocus ) {
                clearTimeout( this.searchTimeout );

                this.searchTimeout  =   setTimeout( () => {
                    this.submitSearch( this.barcode );
                }, 200 );
            }
        }
    },
    mounted() {
        this.loadCategories();

        this.settingsSubscriber         =   POS.settings.subscribe( settings => {
            this.settings               =   settings;
            this.$forceUpdate();
        });

        this.optionsSubscriber          =   POS.options.subscribe( options => {
            this.options                =   options;
            this.$forceUpdate();
        });

        this.breadcrumbsSubsribe        =   POS.breadcrumbs.subscribe( ( breadcrumbs ) => {
            this.breadcrumbs            =   breadcrumbs;
            this.$forceUpdate();
        });
        this.visibleSectionSubscriber   =   POS.visibleSection.subscribe( section => {
            this.visibleSection         =   section;
            this.$forceUpdate();
        });

        this.orderSubscription      =   POS.order.subscribe( order => this.order = order );

        this.interval   =   setInterval( () => this.checkFocus(), 500 );

        /**
         * let's register hotkeys
         */
        for( let shortcut in nsShortcuts ) {
            /**
             * let's declare only shortcuts that
             * works on the pos grid and that doesn't
             * expect any popup to be visible
             */
            if ([
                    'ns_pos_keyboard_quick_search',
                ].includes( shortcut ) ) {
                nsHotPress
                    .create( 'search-popup' )
                    .whenNotVisible([ '.is-popup', '#product-search' ])
                    .whenPressed( nsShortcuts[ shortcut ] !== null ? nsShortcuts[ shortcut ].join( '+' ) : null, ( event ) => {
                        event.preventDefault();
                        this.openSearchPopup();
                });
            }

            /**
             * let's declare only shortcuts that
             * works on the pos grid and that doesn't
             * expect any popup to be visible
             */
            if ([
                    'ns_pos_keyboard_toggle_merge',
                ].includes( shortcut ) ) {
                nsHotPress
                    .create( 'toggle-merge' )
                    .whenNotVisible([ '.is-popup' ])
                    .whenPressed( nsShortcuts[ shortcut ] !== null ? nsShortcuts[ shortcut ].join( '+' ) : null, ( event ) => {
                        event.preventDefault();
                        this.posToggleMerge();
                });
            }
        }

    },
    unmounted() {
        this.orderSubscription.unsubscribe();
        this.breadcrumbsSubsribe.unsubscribe();
        this.visibleSectionSubscriber.unsubscribe();
        this.settingsSubscriber.unsubscribe();
        this.optionsSubscriber.unsubscribe();

        clearInterval( this.interval );

        nsHotPress.destroy( 'search-popup' );
        nsHotPress.destroy( 'toggle-merge' );
    },
    methods: {
        __, 
        nsCurrency,

        switchTo,

        posToggleMerge() {
            POS.set( 'ns_pos_items_merge', ! this.settings.ns_pos_items_merge );
        },

        /**
         * @deprecated
         */
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

        hasNoFeatured( product ) {
            return product.galleries && product.galleries.length > 0 && product.galleries.filter( i => i.featured ).length === 0;
        },

        submitSearch( value ) {
            if ( value.length > 0 ) {
                nsHttpClient.get( `/api/products/search/using-barcode/${value}` )
                    .subscribe({
                        next: result => {
                            this.barcode     =   '';
                            POS.addToCart( result.product );
                        },
                        error: ( error ) => {
                            this.barcode     =   '';
                            nsSnackBar.error( error.message ).subscribe();
                        }
                    })
            }
        },

        checkFocus() {
            if ( this.options.ns_pos_force_autofocus ) {
                const popup     =   document.querySelectorAll( '.is-popup' );

                /**
                 * We don't force focus if
                 * any popup is visible.
                 */
                if ( popup.length === 0 ) {
                    this.$refs.search.focus();
                }
            }
        },

        loadCategories( parent ) {
            this.isLoading  =   true;
            nsHttpClient.get( `/api/categories/pos/${ parent ? parent.id : ''}` )
                .subscribe({
                    next: (result ) => {
                        this.categories         =   result.categories;
                        this.products           =   result.products;
                        this.previousCategory   =   result.previousCategory;
                        this.currentCategory    =   result.currentCategory;
                        this.updateBreadCrumb( this.currentCategory );
                        this.isLoading  =   false;
                    },
                    error: ( error ) => {
                        this.isLoading  =   false;
                        return nsSnackBar.error( __( 'An unexpected error occurred.' ) ).subscribe();
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
