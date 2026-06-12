<template>
    <div class="marketplace-shell w-full flex flex-col gap-6">

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[300px_minmax(0,1fr)]">
            <aside class="marketplace-filters self-start xl:sticky xl:top-6">
                <div class="rounded-lg border border-box-edge bg-box-background shadow-[0_24px_80px_-48px_rgba(15,23,42,0.28)] overflow-hidden">

                    <div class="space-y-6 p-5">
                        <section>
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-fontcolor">{{ __( 'Categories' ) }}</h4>
                                    <p class="text-xs text-fontcolor-soft">{{ __( 'Pick one or more categories.' ) }}</p>
                                </div>
                                <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                                    {{ selectedCategories.length }}
                                </span>
                            </div>

                            <div v-if="normalizedCategories.length > 0" class="space-y-2">
                                <button
                                    v-for="category in normalizedCategories"
                                    :key="category.key"
                                    type="button"
                                    class="group flex w-full items-center gap-3 rounded-2xl border px-3 py-3 text-left transition-all duration-200"
                                    :class="isCategorySelected( category.key ) ? 'border-primary/30 bg-primary/10 text-fontcolor shadow-[0_12px_24px_-18px_rgba(59,130,246,0.8)]' : 'border-box-edge bg-background text-fontcolor hover:border-primary/20 hover:bg-box-elevation-hover'"
                                    @click="toggleSelection( selectedCategories, category.key )">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl transition-all duration-200"
                                        :class="isCategorySelected( category.key ) ? 'bg-primary text-white' : 'bg-box-elevation-hover text-fontcolor-soft group-hover:bg-primary/10 group-hover:text-primary'">
                                        <img
                                            v-if="category.icon"
                                            :src="category.icon"
                                            class="h-full w-full object-cover">
                                        <svg v-else viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                                            <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="truncate text-sm font-medium">{{ category.label }}</p>
                                            <span v-if="category.count !== null" class="rounded-full bg-box-elevation-hover px-2 py-0.5 text-[11px] font-semibold text-fontcolor-soft">
                                                {{ category.count }}
                                            </span>
                                        </div>
                                        <p v-if="category.description" class="mt-0.5 line-clamp-2 text-xs text-fontcolor-soft">
                                            {{ category.description }}
                                        </p>
                                    </div>

                                    <span
                                        class="flex h-5 w-5 items-center justify-center rounded-full border text-[10px] font-bold transition-colors"
                                        :class="isCategorySelected( category.key ) ? 'border-primary bg-primary text-white' : 'border-box-edge text-transparent group-hover:border-primary/30 group-hover:text-primary'">
                                        ✓
                                    </span>
                                </button>
                            </div>

                            <div v-else class="rounded-2xl border border-dashed border-box-edge bg-background p-4 text-sm text-fontcolor-soft">
                                {{ __( 'No categories available yet.' ) }}
                            </div>
                        </section>
                    </div>
                </div>
            </aside>

            <div class="space-y-4">
                <div class="relative rounded-[18px] bg-box-background">
                    <svg :class="searchQuery && searchQuery.length > 0 ? 'bg-primary text-white' : ''" @click="loadItems()" class="pointer-events-none  rounded-full absolute left-2 top-1/2 p-1 -translate-y-1/2 text-fontcolor-soft" width="30" height="30" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M11.3404 18.6798C15.6446 18.6798 19.1339 15.1905 19.1339 10.8863C19.1339 6.58205 15.6446 3.09277 11.3404 3.09277C7.03615 3.09277 3.54688 6.58205 3.54688 10.8863C3.54688 15.1905 7.03615 18.6798 11.3404 18.6798Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M16.8516 16.3975L21.3607 20.9066" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    <input
                        @keyup.enter="loadItems()"
                        id="marketplace-search"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search marketplace..."
                        class="h-12 w-full rounded-[18px] border-2 border-input-edge bg-background px-11 pr-4 text-sm text-fontcolor outline-none transition-colors placeholder:text-fontcolor-soft focus:border-primary/60 focus:ring-4 focus:ring-primary/10"
                    >
                </div>
                <div class="marketplace-content grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
                    <template v-if="result.data.length === 0">
                        <div v-if="!loading" class="col-span-full rounded-[28px] border border-dashed border-box-edge bg-box-background py-16 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7h16l-1.5 10.5A2 2 0 0 1 16.52 19H7.48a2 2 0 0 1-1.98-1.5L4 7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                    <path d="M9 7a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-semibold text-fontcolor">{{ __( 'No items found' ) }}</h2>
                            <p class="mt-2 text-sm text-fontcolor-soft">{{ __( 'Try a different combination of search or categories.' ) }}</p>
                        </div>
                        <div v-else class="col-span-full flex w-full items-center justify-center py-16">
                            <ns-spinner></ns-spinner>
                        </div>
                    </template>
                    <template v-else>
                        <ns-marketplace-item
                            v-for="item in result.data"
                            :key="item.id"
                            :item="item"
                            @buy="handleBuy( $event )"
                            @install="handleInstall( $event )" />
                    </template>
                </div>

                <div v-if="hasPagination" class="marketplace-pagination flex flex-wrap items-center justify-center gap-2 pt-2">
                    <button
                        v-for="( link, index ) in paginationLinks"
                        :key="index"
                        type="button"
                        class="h-10 min-w-10 rounded-full border px-4 text-sm font-medium transition-colors"
                        :class="getPaginationClass( link )"
                        :disabled="! link.page || loading"
                        @click="goToPage( link )">
                        {{ getPaginationLabel( link.label ) }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import NsMarketplaceItem from './ns-marketplace-item.vue';
import NsMynexoposLicenseSelection from './ns-mynexopos-license-selection.vue';
import NsMynexopos from './ns-mynexopos.vue';

declare const nsSnackBar;
declare const nsHttpClient;
declare const Popup;
declare const __;

type MarketplaceFilterOption = string | number | {
    id?: string | number;
    order?: number;
    featured?: boolean;
    created_at?: string;
    primary_locale?: string;
    translations?: Array<{
        locale: string;
        description?: string;
        name?: string;
        short_description?: string;
        slug?: string;
        updated_at?: string;
    }>;
    icon?: string | null;
};

interface MarketplacePaginationLink {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
}

interface MarketplacePagination {
    data: Record<string, any>[];
    meta: {
        links: MarketplacePaginationLink[];
        current_page: number;
        last_page: number;
    };
}

export default {
    props: {
        authenticate: {
            type: Boolean,
            default: false
        },
        isConnected: {
            type: Boolean,
            default: false
        },
        categories: {
            type: Array,
            default: () => []
        },
        tags: {
            type: Array,
            default: () => []
        }
    },
    name: 'ns-marketplace',
    emits: [ 'filters-change', 'search-change' ],
    data() {
        return {
            searchQuery: '',
            selectedCategories: [] as Array<string | number>,
            selectedTags: [] as Array<string | number>,
            result: {
                data: [],
                meta: {
                    links: [],
                    current_page: 1,
                    last_page: 1,
                },
            } as MarketplacePagination,
            loading: false,
            categories: [],
        }
    },
    components: {
        'ns-marketplace-item': NsMarketplaceItem
    },
    computed: {
        normalizedCategories() {
            return this.categories.map( ( item: MarketplaceFilterOption, index: number ) => this.normalizeFilterOption( item, index, 'category' ) );
        },

        selectedFiltersCount() {
            return this.selectedCategories.length + this.selectedTags.length + ( this.searchQuery.trim().length > 0 ? 1 : 0 );
        },

        hasSelectedFilters() {
            return this.selectedFiltersCount > 0;
        },

        paginationLinks() {
            return this.result.meta?.links || [];
        },

        hasPagination() {
            return ( this.result.meta?.last_page || 1 ) > 1 && this.paginationLinks.length > 0;
        }
    },
    methods: {
        __,
        normalizeFilterOption( item: MarketplaceFilterOption, index: number, kind: 'category' ) {
            if ( typeof item === 'string' || typeof item === 'number' ) {
                const label = String( item );

                return {
                    key: item,
                    label,
                    description: '',
                    count: null,
                    icon: null,
                    shortLabel: label.charAt( 0 ).toUpperCase(),
                };
            }

            const key = item.id ?? `${kind}-${index}`;
            const label = item.translations?.[0]?.name ?? String( key );

            return {
                key,
                label,
                description: item.translations?.[0]?.description ?? '',
                icon: item.icon ?? null,
                shortLabel: label.slice( 0, 1 ).toUpperCase()
            };
        },

        emitFiltersChange() {
            this.$emit( 'filters-change', {
                search: this.searchQuery,
                categories: [ ...this.selectedCategories ],
                tags: [ ...this.selectedTags ]
            } );
        },

        isCategorySelected( key: string | number ) {
            return this.selectedCategories.includes( key );
        },

        isTagSelected( key: string | number ) {
            return this.selectedTags.includes( key );
        },

        toggleSelection( collection: Array<string | number>, key: string | number ) {
            const index = collection.indexOf( key );

            if ( index === -1 ) {
                collection.push( key );
            } else {
                collection.splice( index, 1 );
            }
        },

        clearFilters() {
            this.searchQuery = '';
            this.selectedCategories = [];
            this.selectedTags = [];
            this.emitFiltersChange();
            this.$emit( 'search-change', this.searchQuery );
        },

        handleInstall( item: Record<string, any> ) {
            if ( ! this.isConnected ) {
                return Popup.show( NsMynexopos )
            }

            item.isInstalling = true;

            nsHttpClient.get( `/api/marketplace/licenses/${item.id}` ).subscribe({
                next: async (licenses) => {
                    try {
                        console.log({ licenses})
                        const license = await new Promise( ( resolve, reject ) => {
                            Popup.show( NsMynexoposLicenseSelection, { item, licenses, resolve, reject })
                        });
    
                        this.assignLicense( license, item )
                    } catch( exception ) {
                        console.log({ exception })
                        item.isInstalling = false;
                    }
                },
                error: err => {
                    item.isInstalling = false;

                    nsSnackBar.error( err.message || __( 'An error occurred while installing the module.' ) );
                    // Handle error, maybe show a notification
                }
            })
        },

        loadCategories() {
                nsHttpClient.get( `/api/marketplace/categories` ).subscribe({
                    next: categories => {
                        this.categories = categories;
                    },
                    error: err => {
                        // Handle error, maybe show a notification
                    }
                })
        },

        assignLicense( license, item ) {
            nsHttpClient.post( `/api/marketplace/download`, { item_id: item.id, license_id: license.license_uuid } ).subscribe({
                next: response => {
                    nsSnackBar.success( __( 'Module downloaded and installed successfully.' ) );
                    this.loadItems( this.result.meta.current_page );
                },
                error: err => {
                    item.isInstalling = false;
                    nsSnackBar.error( err.message || __( 'An error occurred while downloading the module.' ) );
                }
            })
        },

        loadItems( page = 1 ) {
            this.loading = true;
            let url = `/api/marketplace/modules?per_page=12&page=${page}`;

            const categoriesID = this.selectedCategories.map( String ).join( ',' );

            if ( categoriesID ) {
                url = url.concat( `&categories=${categoriesID}` );
            }

            /**
             * if the "sarchQuery" has a value, we need to add it to the URL as well. 
             */
            if ( this.searchQuery.trim().length > 0 ) {
                url = url.concat( `&search=${encodeURIComponent( this.searchQuery.trim() )}` );
            }

            nsHttpClient.get( url ).subscribe({
                next: pagination => {
                    this.loading = false;

                    /**
                     * We need to add some state to make sure to 
                     * update the UI accordingly.
                     */
                    pagination.data.forEach( item => {
                        item.isAddingToCart = false;
                        item.isInstalling = false;
                    });

                    this.result = pagination;
                },
                error: err => {
                    this.loading = false;
                }
            })
        },

        handleBuy( item ) {
            if ( ! this.isConnected ) {
                return Popup.show( NsMynexopos )
            }

            item.isAddingToCart = true;
            
            nsHttpClient.post( `/api/marketplace/add-to-cart/`, { item_id: item.id } ).subscribe({
                next: response => {
                    if( response.data.action === 'already-on-cart' ) {
                        nsSnackBar.info( __( 'You\'re redirected to your card to complete the purchase.' ) );
                        document.location = response.data.redirect;
                    }

                    if ( response.data.action === 'added-to-cart' ) {
                        nsSnackBar.success( __( 'Item added to cart. Redirecting you to the cart...' ) );
                        document.location = response.data.redirect;
                    }
                },
                error: err => {
                    item.isAddingToCart = false;
                    nsSnackBar.error( err.message || __( 'An error occurred while adding the item to the cart.' ) );
                    // Handle error, maybe show a notification
                }
            })
        },

        goToPage( link: MarketplacePaginationLink ) {
            if ( link.page && ! this.loading ) {
                this.loadItems( link.page );
            }
        },

        getPaginationLabel( label: string ) {
            return label
                .replace( '&laquo;', '' )
                .replace( '&raquo;', '' )
                .trim();
            },

        getPaginationClass( link: MarketplacePaginationLink ) {
            if ( ! link.page || this.loading ) {
                return 'bg-input-disabled text-fontcolor-soft cursor-not-allowed border-box-edge';
            }

            if ( link.active ) {
                return 'bg-primary border-primary text-white shadow-[0_12px_24px_-18px_rgba(59,130,246,0.9)]';
            }

            return 'bg-box-background text-fontcolor border-box-edge hover:bg-box-elevation-hover hover:border-primary/25';
        }
    },
    watch: {
        selectedCategories: {
            handler() {
                this.loadItems();
            },
            deep: true
        },
    },
    mounted() {
        if ( this.authenticate ) {
            Popup.show( NsMynexopos );
        }

        this.loadItems();
        this.loadCategories();
        this.emitFiltersChange();
    }
}
</script>
