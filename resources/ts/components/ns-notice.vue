<template>
    <div :class="'bg-' + actualColor + '-primary border-l-4 border-' + actualColor + '-tertiary text-' + actualColor + '-primary'" class="p-4" role="alert">
        <p class="font-bold"><slot name="title"></slot></p>
        <p><slot name="description"></slot></p>
    </div>
</template>
<script>
export default {
    name: 'ns-notice',
    props: [ 'color' ],
    computed: {
        actualColor() {
            return this.color || 'blue';
        }
    }
}
</script>