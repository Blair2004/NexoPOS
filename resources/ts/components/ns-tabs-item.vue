<template>
    <div :label="label" :identifier="identifier" class="ns-tab-item">
        <div v-if="selectedTab === identifier" class="border rounded flex-auto" :class="( padding || 'p-4' )">
            <slot></slot>
        </div>
    </div>
</template>
<script>
export default {
    data() {
        return {
            selectedTab: '',
            tabStateSubscriber: null
        }
    },
    computed: {
        // ....
    },
    mounted() {
        this.tabStateSubscriber     =   this.$parent.tabState.subscribe( identifier => {
            this.selectedTab    =   identifier;
        })
    },
    beforeUnmount() {
        this.tabStateSubscriber.unsubscribe();
    },
    props: [ 'label', 'identifier', 'padding' ],
}
</script>