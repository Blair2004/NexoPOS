<template>
    <div class="ns-box shadow w-[90vw] md:w-[80vw] lg:w-[70vw] xl:w-[60vw] h-[90vh] md:h-[80vh] lg:h-[70vh] xl:h-[60vh] flex flex-col">
        <div class="flex-shrink-0 flex justify-between items-center p-2 border-b ns-box-header">
            <div>
                <h1 class="text-xl font-bold text-fontcolor text-center">{{ __( 'Drivers' ) }}</h1>
            </div>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="ns-box-body flex-auto">

        </div>
        <div class="ns-box-footer p-2 border-t flex justify-end">
            <ns-button @click="proceed()" type="info">{{ __( 'Skip' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

export default {
    methods: {
        __,
        popupResolver,
        popupCloser,
    },
    mounted() {
        this.popupCloser();
    }
}
</script>