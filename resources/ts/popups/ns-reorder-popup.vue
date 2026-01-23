<template>
    <div class="w-95vw h-95vh flex flex-col shadow-lg md:w-[80vw] md:h-[80vh] ns-box">
        <div class="header ns-box-header border-b border-box-edge flex justify-between p-2 items-center">
            <h3>{{ __( 'Reorder Categories & Products' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="ns-box-body flex-auto overflow-hidden flex flex-col">
            <!-- Navigation breadcrumb -->
            <div class="p-2 border-b border-box-edge">
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
                <div v-else class="space-y-2">
                    <template v-for="(item, index) of items" :key="item.id">
                        <!-- Intermediate drop zone -->
                        <div :data-index="index" class="intermediate-dropzone anim-duration-500">
                            <div></div>
                        </div>
                        
                        <!-- Draggable item -->
                        <ns-dropzone>
                            <ns-draggable
                                :widget="item"
                                @drag-start="handleStartDragging"
                                @drag-end="handleEndDragging($event, index)"
                                >
                                <div class="border border-box-edge rounded p-3 flex items-center space-x-3 bg-box-elevation hover:bg-box-elevation-hover shadow-sm">
                                    <div class="widget-handle drag-handle cursor-move text-secondary hover:text-primary">
                                        <i class="las la-grip-vertical text-2xl"></i>
                                    </div>
                                    <div class="flex-auto">
                                        <div class="font-semibold">{{ item.name }}</div>
                                        <div class="text-sm text-fontcolor-soft" v-if="itemType === 'category'">
                                            {{ item.total_items || 0 }} {{ __( 'items' ) }}
                                        </div>
                                        <div class="text-sm text-fontcolor-soft" v-else>
                                            {{ item.sku }}
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button 
                                            v-if="itemType === 'category' && canNavigateInto(item)"
                                            @click="navigateInto(item)"
                                            class="px-3 py-2 rounded border border-box-edge hover:bg-box-elevation-hover"
                                            :title="__( 'View contents' )">
                                            <i class="las la-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </ns-draggable>
                        </ns-dropzone>
                    </template>
                </div>
            </div>
        </div>
        <div class="ns-box-footer border-t flex justify-between p-2">
            <div>
                <ns-button @click="close()" type="default">{{ __( 'Cancel' ) }}</ns-button>
            </div>
            <div>
                <ns-button @click="saveOrder()" type="info" :disabled="!hasChanges || saving">
                    <ns-spinner class="my-[4px]" v-if="saving" size="4" border="2"></ns-spinner>
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

import nsDropzone from '~/components/ns-dropzone.vue';
import nsDraggable from '~/components/ns-draggable.vue';

export default {
    name: 'ns-reorder-popup',
    components: {
        'ns-dropzone': nsDropzone,
        'ns-draggable': nsDraggable,
    },
    props: ['popup'],
    data() {
        return {
            isDragging: false,
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
        
        // Setup mouse move listener for intermediate drop zones
        document.addEventListener('mousemove', this.handleMouseMove);
    },
    beforeUnmount() {
        document.removeEventListener('mousemove', this.handleMouseMove);
    },
    methods: {
        __,
        popupCloser,
        popupResolver,

        handleMouseMove(event) {
            if (!this.isDragging) {
                return;
            }
            
            // Handle intermediate drop zones (between items)
            const intermediateDropZones = document.querySelectorAll('.intermediate-dropzone');
            
            intermediateDropZones.forEach((dropZone) => {
                const position = dropZone.getBoundingClientRect();
                const { left, top, right, bottom } = position;
                const { clientX, clientY } = event;
    
                if (clientX >= left && clientX <= right && clientY >= top && clientY <= bottom) {
                    dropZone.setAttribute('hovered', 'true');
                    dropZone.classList.add('slide-fade-entrance');
                } else {
                    dropZone.setAttribute('hovered', 'false');
                    dropZone.classList.remove('slide-fade-entrance');
                }
            });
            
            // Handle regular drop zones (swap items)
            const dropZones = document.querySelectorAll('.ns-drop-zone');
            
            dropZones.forEach((dropZone) => {
                const position = dropZone.getBoundingClientRect();
                const { left, top, right, bottom } = position;
                const { clientX, clientY } = event;
    
                if (clientX >= left && clientX <= right && clientY >= top && clientY <= bottom) {
                    dropZone.setAttribute('hovered', 'true');
                } else {
                    dropZone.setAttribute('hovered', 'false');
                }
            });
        },

        handleStartDragging(item) {
            this.isDragging = true;
        },

        handleEndDragging(draggedItem, originalIndex) {
            this.isDragging = false;
            
            const hasIntermediateHovered = document.querySelector('.intermediate-dropzone[hovered="true"]');
            const hasDropZoneHovered = document.querySelector('.ns-drop-zone[hovered="true"]');
            
            if (hasIntermediateHovered) {
                // Move to specific position (insert between items)
                const targetIndex = parseInt(hasIntermediateHovered.getAttribute('data-index'));
                
                // Only reorder if position actually changed
                if (targetIndex !== originalIndex) {
                    // Remove from original position
                    this.items.splice(originalIndex, 1);
                    
                    // Insert at new position
                    // If dragging down, targetIndex is already correct since we removed the item
                    // If dragging up, targetIndex is already where we want it
                    const insertIndex = targetIndex > originalIndex ? targetIndex - 1 : targetIndex;
                    this.items.splice(insertIndex, 0, draggedItem);
                    
                    // Check if order actually changed
                    this.checkForChanges();
                }
                
                // Clean up hover state
                hasIntermediateHovered.setAttribute('hovered', 'false');
                hasIntermediateHovered.classList.remove('slide-fade-entrance');
            } else if (hasDropZoneHovered) {
                // Swap with the hovered item
                const hoveredDraggable = hasDropZoneHovered.querySelector('.ns-draggable-item');
                
                if (hoveredDraggable) {
                    // Find the hovered item in the items array
                    const hoveredItem = this.items.find(item => {
                        // Match by comparing item properties or using a unique identifier
                        return hoveredDraggable.querySelector('.font-semibold')?.textContent?.trim() === item.name;
                    });
                    
                    if (hoveredItem && hoveredItem.id !== draggedItem.id) {
                        const hoveredIndex = this.items.indexOf(hoveredItem);
                        
                        // Swap the two items
                        this.items[originalIndex] = hoveredItem;
                        this.items[hoveredIndex] = draggedItem;
                        
                        // Check if order actually changed
                        this.checkForChanges();
                    }
                }
                
                // Clean up hover state
                hasDropZoneHovered.setAttribute('hovered', 'false');
            }
            
            // Remove any lingering special effects
            this.removeSpecialEffect();
        },
        
        checkForChanges() {
            // Compare current items order with original
            const currentOrder = this.items.map(item => item.id).join(',');
            const originalOrder = this.originalItems.map(item => item.id).join(',');
            this.hasChanges = currentOrder !== originalOrder;
        },

        removeSpecialEffect() {
            const draggableItems = document.querySelectorAll('.ns-draggable-item');
            draggableItems.forEach(item => {
                item.removeAttribute('style');
            });
        },

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
<style scoped>
.intermediate-dropzone[hovered="true"] > div {
    animation: slide-fade-entrance 0.3s ease-out;
}

@keyframes slide-fade-entrance {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>