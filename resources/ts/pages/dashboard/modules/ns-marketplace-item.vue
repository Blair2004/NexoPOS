<template>
    <div class="shadow ns-box rounded flex flex-col">
        <div class="min-h-[100px] bg-red-200">
            <img :src="item.thumbnail" alt="Module Image" class="w-full h-32 object-cover rounded-t">
        </div>
        <div class="p-2 flex-auto flex flex-col gap-2">
            <div class="flex justify-between items-center">
                <h3 class="font-bold">{{ item.name}}</h3>
                <span class="text-xs">v1.0.0</span>
            </div>
            <p>{{ description }}</p>
        </div>
        <div class="flex justify-between items-center p-2 gap-2">
            <div class="flex gap-2">
                <span class="text-sm text-gray-500">${{ item.regular_price }}</span>
                <span class="text-sm text-gray-500">
                    <i class="las la-star text-yellow-500"></i>
                    {{ item.rating_avarage }} ({{ item.rating_count }})
                </span>
            </div>
            <div class="flex gap-2">
                <ns-button class="info text-xs p-0">
                    <i class="las la-shopping-basket"></i>
                </ns-button>
                <ns-button @click="$emit('install', item)" class="text-xs p-0">Install</ns-button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
export default {
    props: [ 'item' ],
    computed: {
        description() {
            const description = this.item.description || '';
            const limit = 100;

            return description.length > limit ? description.substring( 0, limit ) + '...' : description;
        }
    }
}
</script>