<template>
    <div :class="selectedTab.identifier !== identifier ? 'hidden' : ''" :label="label" :identifier="identifier" class="ns-tab-item flex flex-auto overflow-hidden">
        <div v-if="selectedTab.identifier === identifier" class="border rounded flex-auto overflow-y-auto" :class="( padding || 'p-4' )">
            <slot></slot>
        </div>
    </div>
</template>
<script>
export default {
    data() {
        return {
            selectedTab: {},
            tabStateSubscriber: null
        }
    },
    computed: {
        // ....
    },
    mounted() {
        this.tabStateSubscriber     =   this.$parent.tabState.subscribe( tab => {
            this.selectedTab    =   tab;
        })
    },
    unmounted() {
        this.tabStateSubscriber.unsubscribe();
    },
    props: [ 'label', 'identifier', 'padding' ],
}
</script>