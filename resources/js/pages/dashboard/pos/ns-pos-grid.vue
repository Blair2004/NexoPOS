<template>
    <div id="pos-grid" class="rounded shadow bg-white overflow-hidden flex-auto">
        <div id="grid-header" class="p-2 border-b border-gray-200">
            <div class="border rounded flex border-gray-400 overflow-hidden">
                <button class="w-10 h-10 bg-gray-200 border-r border-gray-400">S</button>
                <button class="w-10 h-10 shadow-inner bg-gray-300 border-r border-gray-400">S</button>
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

                <template v-if="hasCategories">
                    <div :key="category.id" v-for="category of categories" class="hover:bg-gray-200 cursor-pointer border h-40 border-gray-200 flex flex-col items-center justify-center">
                        <div class="w-24 h-24 bg-red-200"></div>
                        <h3 class="text-sm py-2 text-gray-700">{{ category.name }}</h3>
                    </div>
                </template>

                <!-- Looping Products -->

                <template v-if="! hasCategories">
                    <div :key="product.id" v-for="product of products" class="border h-40 border-gray-200">1</div>
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
            categories: []
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
        loadCategories( parent = '' ) {
            nsHttpClient.get( `/api/nexopos/v4/categories/pos/${parent}` )
                .subscribe( result => {
                    this.categories     =   result.categories;
                    this.products       =   result.products;
                });
        }
    }
}
</script>