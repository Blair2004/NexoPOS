<template>
    <div id="confirm-popup" :class="size" class="flex flex-col shadow-lg w-5/7-screen md:w-4/7-screen lg:w-2/7-screen">
        <div class="flex items-center justify-center flex-col flex-auto p-4">
            <h2 class="text-xl md:text-3xl font-body text-center">{{ title }}</h2>
            <p class="py-4 text-sm md:text-base text-center">{{ message }}</p>
        </div>
        <div class="action-buttons flex border-t">
            <button class="flex-auto rounded-none w-1/2 h-16 flex items-center justify-center uppercase" @click="emitAction( true )">{{ __( 'Yes' ) }}</button>
            <hr class="border-r h-16">
            <button class="flex-auto rounded-none w-1/2 h-16 flex items-center justify-center uppercase" @click="emitAction( false )">{{ __( 'No' ) }}</button>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang';
import popupResolver from '@/libraries/popup-resolver';
import popupCloser from '@/libraries/popup-closer';

export default {
    data() {
        return {
            title: '',
            message: ''
        }
    },
    computed: {
        size() {
            return this.$popupParams.size || 'h-full w-full'
        }
    },
    mounted() {
        this.title          =   this.$popupParams.title;
        this.message        =   this.$popupParams.message;
        
        this.popupCloser();
    },
    methods: {
        __,
        popupResolver,
        popupCloser,
        
        emitAction( action ) {
            this.$popupParams.onAction( action );
            this.$popup.close();
        }
    }
}
</script>