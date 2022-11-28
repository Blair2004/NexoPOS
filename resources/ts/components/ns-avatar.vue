<template>
    <div class="flex justify-between items-center flex-shrink-0">
        <span class="hidden md:inline-block px-2">{{ __( 'Howdy, {name}' ).replace( '{name}', this.displayName ) }}</span>
        <span class="md:hidden px-2">{{ displayName }}</span>
        <div class="px-2">
            <div class="overflow-hidden rounded-full bg-gray-600">
                <ns-avatar-image :url="avatarUrl" :name="displayName"></ns-avatar-image>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import nsAvatarImage from './ns-avatar-image.vue';

export default {
    methods: {
        __
    },
    components: {
        nsAvatarImage
    },
    name: 'ns-avatar',
    data() {
        return {
            svg: '',
        }
    },
    computed: {
        avatarUrl() {
            return this.url.length === 0 ? '' : this.url;
        }
    },
    props: [ 'url', 'display-name' ]
}
</script>