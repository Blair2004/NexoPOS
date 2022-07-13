<template>
    <div :class="( padding || 'p-4' )" class="ns-tab-item border flex-auto" v-if="$parent.active === identifier">
        <slot></slot>
    </div>
</template>
<script>
export default {
    data() {
        return {}
    },
    mounted() {
    },
    props: [ 'label', 'identifier', 'padding' ],
}
</script>