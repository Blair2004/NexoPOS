<template>
    <div class="h-full flex-auto flex flex-col" id="pos-container">
        <div class="flex overflow-hidden flex-shrink-0 px-2 pt-2">
            <div class="-mx-2 flex overflow-x-auto pb-1">
                <div class="header-buttons flex px-2 flex-shrink-0" :key="index" v-for="(component,index) of buttons">
                    <component :is="component"></component>
                </div>
            </div>
        </div>
        <div class="flex-auto overflow-hidden flex p-2">
            <div class="flex flex-auto overflow-hidden -m-2">
                <div :class="visibleSection === 'both' ? 'w-1/2' : 'w-full'" class="flex overflow-hidden p-2" v-if="[ 'both', 'cart' ].includes( visibleSection )">
                    <ns-pos-cart></ns-pos-cart>
                </div>
                <div :class="visibleSection === 'both' ? 'w-1/2' : 'w-full'" class="p-2 flex overflow-hidden" v-if="[ 'both', 'grid' ].includes( visibleSection )">
                    <ns-pos-grid></ns-pos-grid>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import NsPosCart from './ns-pos-cart';
import NsPosGrid from './ns-pos-grid.vue';

export default {
    name: 'ns-pos',
    computed: {
        buttons() {
            return POS.header.buttons;
        }
    },
    mounted() {
        this.visibleSectionSubscriber   =   POS.visibleSection.subscribe( section => {
            this.visibleSection    =   section;
        });
    },
    destroyed() {
        this.visibleSectionSubscriber.unsubscribe();
    },
    data() {
        return {
            visibleSection: null,
            visibleSectionSubscriber: null,
        }
    },
    components: {
        NsPosCart,
        NsPosGrid,
    }
}
</script>