<template>
    <button @click="openReorder()" class="flex justify-center items-center rounded-full text-sm h-10 px-3 ns-crud-button border outline-hidden"><i class="las la-hand-pointer mr-1"></i> {{ __( 'Reorder' ) }}</button>
</template>
<script>
import { __ } from '~/libraries/lang';
import { Popup } from '~/libraries/popup';
import nsReorderPopup from '~/popups/ns-reorder-popup.vue';
export default {
    name: 'nsReorderButton',
    methods: {
        __,
        openReorder() {
            Popup.show( nsReorderPopup );
        }
    }
}
</script>