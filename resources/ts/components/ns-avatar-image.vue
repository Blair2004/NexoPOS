<template>
    <div :class="'h-' + currentSize + ' w-' + currentSize">
        <img v-if="url !== '' && url !== null" :src="url" class="overflow-hidden rounded-full" :alt="name" srcset="">
        <div :class="'h-' + currentSize + ' w-' + currentSize" v-html="svg" v-if="url === '' || url === null"></div>
    </div>
</template>
<script>
import { createAvatar } from '@dicebear/avatars';
import * as style from '@dicebear/avatars-avataaars-sprites';
export default {
    name: 'ns-avatar-image',
    props: [ 'url', 'name', 'size' ],
    data() {
        return {
            svg: '',
            currentSize: 8
        }
    },
    mounted() {
        this.currentSize   =   this.size || 8;
        this.svg = createAvatar(style, {
            seed: this.name,
            // ... and other options
        });
    },
}
</script>