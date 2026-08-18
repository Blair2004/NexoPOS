<template>
    <div class="p-2 flex flex-col gap-4">
        <h1 class="text-xl font-bold">{{ __( 'Completed Guides' ) }}</h1>
        <div class="flex gap-2 flex-col">
            <div v-for="item of paginatedCompletedGuides.data" class="flex gap-2 border rounded-lg shadow border-box-edge p-2">
                <div class="w-12 h-12 flex items-center justify-center">
                    <i v-if="item.icon_class" :class="item.icon_class"></i>
                    <span v-else-if="item.icon" v-html="item.icon"></span>
                </div>
                <div class="flex flex-col">
                    <h2 class="font-bold text-lg text-fontcolor">{{ item.title }}</h2>
                    <p class="text-sm text-fontcolor-soft">{{ item.description }}</p>
                </div>
                <div class="flex flex-auto justify-end">
                    <div class="flex items-center justify-center">
                        <button @click="reset( item.id )" class="border rounded-lg border-input-edge px-4 py-2 hover:bg-primary hover:border-transparent hover:text-white cursor-pointer">{{ __( 'Reset' ) }}</button>
                    </div>
                </div>
            </div>
            <div v-if="paginatedCompletedGuides.data && paginatedCompletedGuides.data.length === 0" class="border rounded-lg border-box-edge shadow flex flex-col items-center justify-center gap-2">
                <div class="flex flex-col items-center justify-center p-4">
                    <div class="text-fontcolor-soft">{{ __( 'No completed guides found.' ) }}</div>
                </div>
            </div>
        </div>
        <ns-paginate @load="loadCompleted" v-if="paginatedCompletedGuides.data && paginatedCompletedGuides.data.length > 0" :pagination="paginatedCompletedGuides"/>
        <h1 class="text-xl font-bold">{{ __( 'Dismissed Guides' ) }}</h1>
        <div class="flex gap-2 flex-col">
            <div v-for="item of paginatedDismissedGuides.data" class="flex gap-2 border rounded-lg shadow border-box-edge p-2">
                <div class="w-12 h-12 flex items-center justify-center">icon</div>
                <div class="flex flex-col">
                    <h2 class="font-bold text-lg text-fontcolor">{{ item.title }}</h2>
                    <p class="text-sm text-fontcolor-soft">{{ item.description }}</p>
                </div>
                <div class="flex flex-auto justify-end">
                    <div class="flex items-center justify-center">
                        <button @click="reset( item.id )" class="border rounded-lg border-input-edge px-4 py-2 hover:bg-primary hover:border-transparent hover:text-white cursor-pointer">{{ __( 'Reset' ) }}</button>
                    </div>
                </div>
            </div>
            <div v-if="paginatedDismissedGuides.data && paginatedDismissedGuides.data.length === 0" class="border rounded-lg border-box-edge shadow flex flex-col items-center justify-center gap-2">
                <div class="flex flex-col items-center justify-center p-4">
                    <div class="text-fontcolor-soft">{{ __( 'No dismissed guides found.' ) }}</div>
                </div>
            </div>
        </div>
        <ns-paginate @load="loadDismissed" v-if="paginatedDismissedGuides.data && paginatedDismissedGuides.data.length > 0" :pagination="paginatedDismissedGuides"/>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { nsConfirmPopup } from '~/components/components';
import { __ } from '~/libraries/lang';

const paginatedDismissedGuides = ref({});
const paginatedCompletedGuides = ref({});

function loadCompleted( path ) {
    nsHttpClient.get( path || '/api/user/guides/completed' ).subscribe({
        next: completed => {
            paginatedCompletedGuides.value = completed;
        }
    })
}

function loadDismissed( path ) {
    nsHttpClient.get( path || '/api/user/guides/dismissed' ).subscribe({
        next: dismissed => {
            paginatedDismissedGuides.value = dismissed;
        }
    })
}

function reset( id ) {
    Popup.show( nsConfirmPopup, {
        title: __( 'Reset Guide' ),
        message: __( 'Are you sure you want to reset this guide? It will be marked as not completed and will be shown to you again.' ),
        onAction: ( action ) => {
            if ( action ) {
                nsHttpClient.post( '/api/user/guides/reset', { guide_id: id } ).subscribe({
                    next: () => {
                        loadCompleted();
                        loadDismissed();
                    }
                })
            }
        }
    })
}

onMounted(() => {
    loadCompleted();
    loadDismissed();
})
</script>