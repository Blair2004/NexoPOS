<template>
    <div id="pos-grid" class="rounded shadow bg-white overflow-hidden flex-auto">
        <div id="grid-header" class="p-2 border-b border-gray-200">
            <div class="border rounded flex border-gray-400 overflow-hidden">
                <button class="w-10 h-10 bg-gray-200 border-r border-gray-400">
                    <i class="las la-search"></i>
                </button>
                <button class="w-10 h-10 shadow-inner bg-gray-300 border-r border-gray-400">
                    <i class="las la-barcode"></i>
                </button>
                <input type="text" class="flex-auto outline-none px-2 bg-gray-100">
            </div>
        </div>
        <div id="grid-breadscrumb" class="p-2 border-gray-200">
            <ul class="flex">
                <li><a href="javascript:void(0)" class="px-3 text-gray-700">Home </a> <i class="las la-angle-right"></i> </li>
                <li><a href="javascript:void(0)" class="px-3 text-gray-700">Mens </a> <i class="las la-angle-right"></i> </li>
                <li><a href="javascript:void(0)" class="px-3 text-gray-700">Shirts </a> <i class="las la-angle-right"></i> </li>
                <li><a href="javascript:void(0)" class="px-3 text-gray-700">Sport </a> <i class="las la-angle-right"></i> </li>
            </ul>
        </div>
        <div id="grid-items" class="overflow-hidden flex-auto">
            <div class="grid grid-cols-6 gap-0 overflow-y-auto">
                
                <!-- Loop Products Or Categories -->

                <template v-if="previousCategory !== false">
                    <div @click="loadCategories( previousCategory )" class="hover:bg-gray-200 cursor-pointer border h-40 border-gray-200 flex flex-col items-center justify-center">
                        <div class="h-full w-full p-2 flex items-center justify-center">
                            <i class="las la-undo font-bold text-5xl"></i>
                        </div>
                    </div>
                </template>

                <template v-if="hasCategories">
                    <div @click="browse( category )" :key="category.id" v-for="category of categories" class="hover:bg-gray-200 cursor-pointer border h-40 border-gray-200 flex flex-col items-center justify-center">
                        <div class="h-full w-full p-2 flex items-center justify-center">
                            <img v-if="category.preview_url" :src="category.preview_url" class="object-center" :alt="category.name">
                            <i class="las la-image text-gray-600 text-6xl" v-if="! category.preview_url"></i>
                        </div>
                        <div class="h-0 w-full">
                            <div class="relative w-full flex items-center justify-center -top-10 h-20 py-2" style="background:rgb(255 255 255 / 73%)">
                                <h3 class="text-sm font-bold text-gray-700 py-2">{{ category.name }}</h3>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Looping Products -->

                <template v-if="! hasCategories">
                    <div @click="addToTheCart( category )" :key="product.id" v-for="product of products"  class="hover:bg-gray-200 cursor-pointer border h-40 border-gray-200 flex flex-col items-center justify-center">
                        <div class="h-full w-full p-2 flex items-center justify-center">
                            <img v-if="product.galleries.filter( i => i.featured === 1 ).length > 0" :src="product.galleries.filter( i => i.featured === 1 )[0].url" class="object-center" :alt="product.name">
                            <i v-if="product.galleries.filter( i => i.featured === 1 ).length === 0" class="las la-image text-gray-600 text-6xl"></i>
                        </div>
                        <div class="h-0 w-full">
                            <div class="relative w-full flex flex-col items-start justify-center -top-10 h-20 p-2" style="background:rgb(255 255 255 / 73%)">
                                <h3 class="text-sm text-gray-700 text-center w-full">{{ product.name }}</h3>
                                <h2 class="text-sm text-gray-800 font-bold text-center w-full">{{ product.sale_price | currency }}</h2>
                            </div>
                        </div>
                    </div>
                </template>
                
                <!-- End Loop -->

            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '../../../bootstrap'
export default {
    name: 'ns-pos-grid',
    data() {
        return {
            products: [],
            categories: [],
            previousCategory: null,
        }
    },
    computed: {
        hasCategories() {
            return this.categories.length > 0;
        }
    },
    mounted() {
        this.loadCategories();
    },
    methods: {
        browse( category ) {
            this.loadCategories( category.id );
        },

        loadCategories( parent = '' ) {
            nsHttpClient.get( `/api/nexopos/v4/categories/pos/${parent || ''}` )
                .subscribe( result => {
                    this.categories         =   result.categories;
                    this.products           =   result.products;
                    this.previousCategory   =   result.previousCategory;
                });
        }
    }
}
</script>