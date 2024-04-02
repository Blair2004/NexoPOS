<template>
    <div :class="actualColor" class="p-4 border-l-4 text-primary ns-notice" role="alert">
        <h2 class="font-bold"><slot name="title"></slot></h2>
        <p class="my-2"><slot name="description"></slot><slot></slot></p>
        <div class="flex">
            <slot name="controls"></slot>
        </div>
    </div>
</template>
<script>
export default {
    name: 'ns-notice',
    props: [ 'color' ],
    computed: {
        actualColor() {
            return this.color || 'info';
        }
    }
}
</script>