<template>
    <div class="flex justify-between items-center flex-shrink-0">
        <span class="hidden md:inline-block px-2">{{ __( 'Howdy, {name}' ).replace( '{name}', this.displayName ) }}</span>
        <span class="md:hidden px-2">{{ displayName }}</span>
        <div class="px-2">
            <div class="w-8 h-8 overflow-hidden rounded-full bg-gray-600">
                <img v-if="avatarUrl !== ''" :src="avatarUrl" class="w-8 h-8 overflow-hidden rounded-full" :alt="displayName" srcset="">
                <div v-html="svg" v-if="avatarUrl === ''"></div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '@/libraries/lang';
import Vue from 'vue';
import { createAvatar } from '@dicebear/avatars';
import * as style from '@dicebear/avatars-avataaars-sprites';

export default Vue.extend({
    methods: {
        __
    },
    data() {
        return {
            svg: '',
        }
    },
    mounted() {
        this.svg = createAvatar(style, {
            seed: this.displayName,
            // ... and other options
        });
    },
    computed: {
        avatarUrl() {
            return this.url.length === 0 ? '' : this.url;
        }
    },
    props: [ 'url', 'display-name' ]
})
</script>