<template>
    <div class="w-95vw h-95vh flex flex-col shadow-lg md:w-[80vw] md:h-[80vh] ns-box">
        <div class="header ns-box-header border-b flex justify-between p-2 items-center">
            <h3>{{ __( 'Reorder Categories & Products' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="ns-box-body flex-auto overflow-hidden flex flex-col">
            <!-- Navigation breadcrumb -->
            <div class="p-2 border-b">
                <div class="flex items-center space-x-2">
                    <button @click="navigateToRoot()" class="px-3 py-1 rounded hover:bg-box-elevation-hover">
                        <i class="las la-home"></i> {{ __( 'Home' ) }}
                    </button>
                    <template v-for="(crumb, index) in breadcrumb" :key="crumb.id">
                        <i class="las la-angle-right"></i>
                        <button @click="navigateToCategory(crumb)" class="px-3 py-1 rounded hover:bg-box-elevation-hover">
                            {{ crumb.name }}
                        </button>
                    </template>
                </div>
            </div>

            <!-- Loading state -->
            <div class="h-full w-full flex justify-center items-center" v-if="loading">
                <ns-spinner></ns-spinner>
            </div>

            <!-- Reorderable items list -->
            <div v-else class="flex-auto overflow-y-auto p-4">
                <div v-if="items.length === 0" class="text-center py-8 text-secondary">
                    {{ __( 'No items to reorder.' ) }}
                </div>
                <draggable 
                    v-else
                    v-model="items" 
                    @end="onDragEnd"
                    item-key="id"
                    handle=".drag-handle"
                    animation="200"
                    class="space-y-2">
                    <template #item="{element, index}">
                        <div class="border rounded p-3 flex items-center space-x-3 bg-box-elevation hover:bg-box-elevation-hover">
                            <div class="drag-handle cursor-move text-secondary hover:text-primary">
                                <i class="las la-grip-vertical text-2xl"></i>
                            </div>
                            <div class="flex-auto">
                                <div class="font-semibold">{{ element.name }}</div>
                                <div class="text-sm text-secondary" v-if="itemType === 'category'">
                                    {{ element.total_items || 0 }} {{ __( 'items' ) }}
                                </div>
                                <div class="text-sm text-secondary" v-else>
                                    {{ element.sku }}
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button 
                                    v-if="itemType === 'category' && canNavigateInto(element)"
                                    @click="navigateInto(element)"
                                    class="px-3 py-2 rounded border hover:bg-box-elevation-hover"
                                    :title="__( 'View contents' )">
                                    <i class="las la-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>
        </div>
        <div class="ns-box-footer border-t flex justify-between p-2">
            <div>
                <ns-button @click="close()" type="default">{{ __( 'Cancel' ) }}</ns-button>
            </div>
            <div>
                <ns-button @click="saveOrder()" type="info" :disabled="!hasChanges || saving">
                    <ns-spinner v-if="saving" size="4" border="2"></ns-spinner>
                    <span v-else>{{ __( 'Save Order' ) }}</span>
                </ns-button>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { __ } from '~/libraries/lang';
import popupResolver from '~/libraries/popup-resolver';
import popupCloser from '~/libraries/popup-closer';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
// @ts-ignore - vuedraggable doesn't have proper TS types
import draggable from 'vuedraggable';

export default {
    name: 'ns-reorder-popup',
    components: {
        draggable
    },
    props: ['popup'],
    data() {
        return {
            loading: true,
            saving: false,
            items: [],
            originalItems: [],
            currentCategory: null,
            breadcrumb: [],
            itemType: 'category', // 'category' or 'product'
            hasChanges: false,
        };
    },
    mounted() {
        this.popupCloser();
        this.loadItems();
    },
    methods: {
        __,
        popupCloser,
        popupResolver,

        close() {
            this.popupResolver(false);
        },

        loadItems(categoryId = null) {
            this.loading = true;
            
            nsHttpClient.get(`/api/categories/${categoryId || ''}?parent=${categoryId ? 'false' : 'true'}`)
                .subscribe({
                    next: (result) => {
                        if (categoryId) {
                            // Load category details to check for subcategories and products
                            nsHttpClient.get(`/api/categories/${categoryId}`)
                                .subscribe({
                                    next: (category) => {
                                        this.currentCategory = category;
                                        
                                        // Check if category has subcategories
                                        nsHttpClient.get(`/api/categories?parent=false`)
                                            .subscribe({
                                                next: (allCategories) => {
                                                    const subcategories = allCategories.filter(c => c.parent_id === categoryId);
                                                    
                                                    if (subcategories.length > 0) {
                                                        // Has subcategories, show them
                                                        this.items = this.sortByPosition(subcategories);
                                                        this.itemType = 'category';
                                                    } else {
                                                        // No subcategories, load products
                                                        this.loadProducts(categoryId);
                                                        return;
                                                    }
                                                    
                                                    this.originalItems = JSON.parse(JSON.stringify(this.items));
                                                    this.hasChanges = false;
                                                    this.loading = false;
                                                }
                                            });
                                    }
                                });
                        } else {
                            // Load root categories
                            const rootCategories = Array.isArray(result) ? result : [];
                            this.items = this.sortByPosition(rootCategories.filter(c => !c.parent_id || c.parent_id === 0));
                            this.itemType = 'category';
                            this.currentCategory = null;
                            this.originalItems = JSON.parse(JSON.stringify(this.items));
                            this.hasChanges = false;
                            this.loading = false;
                        }
                    },
                    error: (error) => {
                        this.loading = false;
                        nsSnackBar.error(error.message || __('Failed to load items'));
                    }
                });
        },

        loadProducts(categoryId) {
            nsHttpClient.get(`/api/categories/${categoryId}/products`)
                .subscribe({
                    next: (products) => {
                        this.items = this.sortByPosition(products);
                        this.itemType = 'product';
                        this.originalItems = JSON.parse(JSON.stringify(this.items));
                        this.hasChanges = false;
                        this.loading = false;
                    },
                    error: (error) => {
                        this.loading = false;
                        nsSnackBar.error(error.message || __('Failed to load products'));
                    }
                });
        },

        sortByPosition(items) {
            return items.sort((a, b) => (a.position || 0) - (b.position || 0));
        },

        canNavigateInto(category) {
            // Can navigate into categories
            return true;
        },

        navigateInto(category) {
            this.breadcrumb.push(category);
            this.loadItems(category.id);
        },

        navigateToCategory(category) {
            // Find index of category in breadcrumb
            const index = this.breadcrumb.findIndex(c => c.id === category.id);
            
            // Remove everything after this category
            this.breadcrumb = this.breadcrumb.slice(0, index + 1);
            
            this.loadItems(category.id);
        },

        navigateToRoot() {
            this.breadcrumb = [];
            this.loadItems(null);
        },

        onDragEnd() {
            this.hasChanges = true;
        },

        saveOrder() {
            this.saving = true;

            // Prepare items with new positions
            const itemsToSave = this.items.map((item, index) => ({
                id: item.id,
                position: index
            }));

            const endpoint = this.itemType === 'category' 
                ? '/api/categories/reorder' 
                : '/api/products/reorder';

            nsHttpClient.post(endpoint, { items: itemsToSave })
                .subscribe({
                    next: (response) => {
                        this.saving = false;
                        this.hasChanges = false;
                        this.originalItems = JSON.parse(JSON.stringify(this.items));
                        nsSnackBar.success(response.message || __('Order saved successfully'));
                    },
                    error: (error) => {
                        this.saving = false;
                        nsSnackBar.error(error.message || __('Failed to save order'));
                    }
                });
        }
    }
};
</script>
