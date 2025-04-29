<template>
    <div :class="sizeSet[currentSize] + ' flex items-center justify-center'">
        <img v-if="url !== '' && url !== null" :src="url" class="overflow-hidden rounded-full" :alt="name" srcset="">
        <div :class="sizeSet[currentSize]" v-html="svg" v-if="url === '' || url === null"></div>
    </div>
</template>
<script>
import { createAvatar } from '@dicebear/core';
import * as style from '@dicebear/avataaars';
export default {
    name: 'ns-avatar-image',
    props: [ 'url', 'name', 'size' ],
    data() {
        return {
            svg: '',
            currentSize: 8,
            sizeSet: {
                8: 'h-8 w-8',
                16: 'h-16 w-16',
                24: 'h-24 w-24',
                32: 'h-32 w-32',
                40: 'h-40 w-40',
                48: 'h-48 w-48',
                56: 'h-56 w-56',
                64: 'h-64 w-64',
                72: 'h-72 w-72',
                80: 'h-80 w-80',
                88: 'h-88 w-88',
                96: 'h-96 w-96',
                104: 'h-104 w-104',
                112: 'h-112 w-112',
                120: 'h-120 w-120',
            }
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