<template>
    <div class="ns-box w-[95vw] md:w-[60vw] lg:w-[40vw] xl:w-[30vw] shadow">
        <div class="p-2 flex justify-between items-center ns-box-header border-b">
            <h3>{{  __( 'Order: {name}' ).replace( '{name}', order.code ) }}</h3>
            <div>
                <ns-close-button @click="popupResolver( false )"></ns-close-button>
            </div>
        </div>
        <div class="box-body">
            <div class="py-4 flex justify-center items-center" v-if="!loaded">
                <ns-spinner></ns-spinner>
            </div>
            <div v-if="fields.length > 0" class="p-2">
                <ns-field v-for="field of fields" :field="field"></ns-field>
            </div>
        </div>
        <div class="border-t ns-box-footer p-2 flex justify-end">
            <ns-button @click="changeStatus()">{{ __( 'Change Status' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

export default {
    name: 'foo-bar',
    methods: {
        __,
        popupResolver,
        popupCloser,
    },
    mounted() {
        console.log( this.order );
    },
    props: [ 'order' ],
}
</script>