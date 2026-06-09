<template>
    <div class="marketplace-header py-4 w-full flex flex-auto flex-col">
        <input v-model="searchQuery" type="text" placeholder="Search marketplace..." class="border-2 p-2 outline-none rounded border-input-edge mb-4 md:mb-0">
    </div>
    <div class="marketplace-content grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template v-if="result.data.length === 0">
            <div v-if="!loading" class="col-span-full text-center py-10">
                <h2 class="text-2xl font-bold">No items found</h2>
                <p class="text-gray-500">Please check back later for new modules.</p>
            </div>
            <div v-else class="w-full flex items-center justify-center col-span-full py-10">
                <ns-spinner></ns-spinner>
            </div>
        </template>
        <template v-else>
            <ns-marketplace-item 
                @install="handleInstall( $event )" v-for="item in result.data" :key="item.id" :item="item"></ns-marketplace-item>
        </template>
    </div>
    <div v-if="hasPagination" class="marketplace-pagination flex flex-wrap items-center justify-center gap-2 mt-4">
        <button
            v-for="( link, index ) in paginationLinks"
            :key="index"
            type="button"
            class="h-8 min-w-8 px-3 rounded border border-box-edge text-sm"
            :class="getPaginationClass( link )"
            :disabled="! link.page || loading"
            @click="goToPage( link )">
            {{ getPaginationLabel( link.label ) }}
        </button>
    </div>
</template>
<script lang="ts">
import NsMarketplaceItem from './ns-marketplace-item.vue';
import NsMynexopos from './ns-mynexopos.vue';

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
    props: [ 'isConnected' ],
    name: 'ns-marketplace',
    data() {
        return {
            searchQuery: '',
            result: {
                data: [],
                meta: {
                    links: [],
                    current_page: 1,
                    last_page: 1,
                },
            } as MarketplacePagination,
            loading: false,
        }
    },
    components: {
        'ns-marketplace-item': NsMarketplaceItem
    },
    computed: {
        paginationLinks() {
            return this.result.meta?.links || [];
        },

        hasPagination() {
            return ( this.result.meta?.last_page || 1 ) > 1 && this.paginationLinks.length > 0;
        }
    },
    methods: {
        handleInstall( item: Record<string, any> ) {
            if ( ! this.isConnected ) {
                Popup.show( NsMynexopos )
            }
        },

        loadItems( page = 1 ) {
            this.loading = true;
            nsHttpClient.get( `/api/marketplace/modules?per_page=3&page=${page}` ).subscribe({
                next: pagination => {
                    this.loading = false;
                    this.result = pagination;
                },
                error: err => {
                    this.loading = false;
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
                return 'bg-input-disabled text-fontcolor-soft cursor-not-allowed';
            }

            if ( link.active ) {
                return 'bg-primary border-secondary text-white';
            }

            return 'bg-box-background text-fontcolor hover:bg-box-elevation-hover';
        }
    },
    mounted() {
        this.loadItems();
    }
}
</script>