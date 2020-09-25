<template>
    <div class="h-full bg-gray-300 flex flex-col" id="pos-container">
        <div class="h-12 flex-shrink-0 px-2 pt-2 flex">
            <div class="-mx-2 flex">
                <div class="flex px-2" :key="index" v-for="(component,index) of buttons">
                    <component :is="component"></component>
                </div>
            </div>
        </div>
        <div class="flex-auto overflow-hidden flex p-2">
            <div class="flex flex-auto overflow-hidden -m-2">
                <div class="w-1/2 flex overflow-hidden p-2">
                    <ns-pos-cart></ns-pos-cart>
                </div>
                <div class="w-1/2 p-2 flex overflow-hidden">
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
    data() {
        return {}
    },
    components: {
        NsPosCart,
        NsPosGrid,
    }
}
</script>