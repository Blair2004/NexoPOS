<template>
    <div class="shadow ns-box rounded flex flex-col">
        <div class="min-h-[100px] bg-red-200">
            <img :src="item.thumbnail" alt="Module Image" class="w-full h-32 object-cover rounded-t">
        </div>
        <div class="p-2 flex-auto flex flex-col gap-2">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-sm">{{ item.name}}</h3>
                <span class="text-xs">v{{ item.latest_version.version}}</span>
            </div>
            <p class="text-sm text-fontcolor-soft">{{ description }}</p>
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
                <template v-if="item.has_purchased === 1">
                    <template v-if="item.is_installed">
                        <template v-if="item.is_up_to_date">
                            <ns-button disabled class="text-xs p-0">
                                <i class="las la-check mr-2"></i>
                                {{ __( 'Installed' ) }}
                            </ns-button>
                        </template>
                        <template v-else>
                            <ns-button :disabled="item.isInstalling"  @click="$emit('install', item)" class="text-xs p-0">
                                <i v-if="!item.isInstalling" class="las la-shopping-basket mr-2"></i>
                                <i v-else class="las la-spinner animate-spin mr-2"></i>
                                {{ __( 'Update' ) }}
                            </ns-button>
                        </template>
                    </template>
                    <template v-else>
                        <ns-button :disabled="item.isInstalling"  @click="$emit('install', item)" class="text-xs p-0">
                            <i v-if="!item.isInstalling" class="las la-shopping-basket mr-2"></i>
                            <i v-else class="las la-spinner animate-spin mr-2"></i>
                            {{ __( 'Install' ) }}
                        </ns-button>
                    </template>
                </template>
                <template v-else>
                    <ns-button :disabled="item.isAddingToCart" @click="$emit('buy', item)" class="info text-xs p-0">
                        <i class="las la-shopping-basket mr-2"></i>
                        <span v-if="item.regular_price > 0">{{ __( 'Buy' ) }}</span>
                        <span v-else>{{ __( 'Get It' ) }}</span>
                    </ns-button>
                </template>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
declare const __;

export default {
    props: [ 'item' ],
    computed: {
        description() {
            const description = this.item.description || '';
            const limit = 100;

            return description.length > limit ? description.substring( 0, limit ) + '...' : description;
        }
    },
    methods: {
        __,
    }
}
</script>